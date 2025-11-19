<?php

namespace App\Jobs;

use App\Enums\StatusPresensi;
use App\Enums\StatusPulang;
use App\Models\Instansi;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SetHadirSiswa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 3600; // 60 menit

    public $failOnTimeout = true;

    // Database batch size untuk insert
    private const DATABASE_BATCH_SIZE = 500;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public int $userId
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tanggalMulai = Carbon::parse($this->data['tanggalMulai']);
        $tanggalSelesai = Carbon::parse($this->data['tanggalSelesai']);
        $catatan = $this->data['catatan'] ?? null;
        $jamDatangBase = $this->data['jamDatang'] ?? null;
        $jamPulangBase = $this->data['jamPulang'] ?? null;

        // Generate range tanggal (exclude weekends)
        $rangeTanggal = $this->generateRangeTanggal($tanggalMulai, $tanggalSelesai);

        // Tentukan siswa IDs
        $siswaIds = $this->getSiswaIds();

        // Validasi
        if (empty($siswaIds) || $rangeTanggal->isEmpty()) {
            $this->sendNotification(
                'Tidak Ada Data',
                '⚠️ Tidak ada siswa atau tanggal yang valid untuk diproses.',
                'warning'
            );

            return;
        }

        // Ambil existing records (SINGLE QUERY)
        $existingRecords = $this->getExistingRecords($siswaIds, $tanggalMulai, $tanggalSelesai);

        // Siapkan data untuk batch insert
        $dataToInsert = $this->prepareDataToInsert(
            $siswaIds,
            $rangeTanggal,
            $existingRecords,
            $jamDatangBase,
            $jamPulangBase,
            $catatan
        );

        $jumlahBerhasil = 0;
        $jumlahDiabaikan = (count($siswaIds) * $rangeTanggal->count()) - count($dataToInsert);

        // Batch insert dengan chunking
        if (! empty($dataToInsert)) {
            $jumlahBerhasil = $this->batchInsert($dataToInsert);
        }

        // Kirim notifikasi sukses
        $this->sendNotification(
            'Penetapan Hadir Selesai',
            "🟢 {$jumlahBerhasil} data berhasil disimpan. 🔴 {$jumlahDiabaikan} data diabaikan.",
            'success'
        );
    }

    /**
     * Generate range tanggal dengan exclude weekends
     */
    private function generateRangeTanggal(Carbon $tanggalMulai, Carbon $tanggalSelesai): \Illuminate\Support\Collection
    {
        $instansi = Instansi::first();
        $rangeTanggal = collect();

        for ($date = $tanggalMulai->copy(); $date->lte($tanggalSelesai); $date->addDay()) {
            // Skip weekend berdasarkan status instansi
            if ($instansi && $instansi->status === 'Negeri') {
                if ($date->isSaturday() || $date->isSunday()) {
                    continue;
                }
            } elseif ($instansi && $instansi->status === 'Swasta') {
                if ($date->isSunday()) {
                    continue;
                }
            }

            $rangeTanggal->push($date->format('Y-m-d'));
        }

        return $rangeTanggal;
    }

    /**
     * Get siswa IDs berdasarkan tipe
     */
    private function getSiswaIds(): array
    {
        if ($this->data['tipe'] === 'single') {
            return [$this->data['namaSiswa']];
        }

        if ($this->data['tipe'] === 'all') {
            return Siswa::where('status', true)->pluck('id')->toArray();
        }

        if ($this->data['tipe'] === 'jabatan') {
            return Siswa::whereHas('jabatan', function ($query) {
                $query->whereIn('jabatan_id', $this->data['jabatan']);
            })->where('status', true)->pluck('id')->toArray();
        }

        return [];
    }

    /**
     * Get existing records dengan SINGLE QUERY
     */
    private function getExistingRecords(array $siswaIds, Carbon $tanggalMulai, Carbon $tanggalSelesai): \Illuminate\Support\Collection
    {
        return PresensiSiswa::whereIn('siswa_id', $siswaIds)
            ->whereBetween('tanggal', [$tanggalMulai->format('Y-m-d'), $tanggalSelesai->format('Y-m-d')])
            ->get()
            ->mapWithKeys(function ($item) {
                // Create unique key: siswaId_tanggal
                $key = $item->siswa_id.'_'.Carbon::parse($item->tanggal)->format('Y-m-d');

                return [$key => true];
            });
    }

    /**
     * Prepare data untuk batch insert
     */
    private function prepareDataToInsert(
        array $siswaIds,
        \Illuminate\Support\Collection $rangeTanggal,
        \Illuminate\Support\Collection $existingRecords,
        ?string $jamDatangBase,
        ?string $jamPulangBase,
        ?string $catatan
    ): array {
        $dataToInsert = [];
        $now = now();

        foreach ($siswaIds as $siswaId) {
            foreach ($rangeTanggal as $tanggal) {
                $key = $siswaId.'_'.$tanggal;

                // Skip jika sudah ada
                if ($existingRecords->has($key)) {
                    continue;
                }

                // Generate random jam datang dan pulang untuk setiap siswa
                $jamDatang = $jamDatangBase ? $this->generateRandomTime($jamDatangBase, -15, 15, '07:00', 'max') : null;
                $jamPulang = $jamPulangBase ? $this->generateRandomTime($jamPulangBase, -15, 15, '16:00', 'min') : null;

                $dataToInsert[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'siswa_id' => $siswaId,
                    'tanggal' => $tanggal,
                    'statusPresensi' => StatusPresensi::Hadir->value,
                    'statusPulang' => StatusPulang::Pulang->value,
                    'jamDatang' => $jamDatang,
                    'jamPulang' => $jamPulang,
                    'catatan' => $catatan,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $dataToInsert;
    }

    /**
     * Generate random time dengan constraint (termasuk detik random)
     *
     * @param  string  $baseTime  Jam dasar (format HH:mm:ss atau HH:mm)
     * @param  int  $minMinutes  Offset minimal dalam menit (bisa negatif)
     * @param  int  $maxMinutes  Offset maksimal dalam menit
     * @param  string  $limitTime  Jam batas (format HH:mm:ss atau HH:mm)
     * @param  string  $limitType  'max' atau 'min'
     * @return string Jam hasil dalam format HH:mm:ss
     */
    private function generateRandomTime(
        string $baseTime,
        int $minMinutes,
        int $maxMinutes,
        string $limitTime,
        string $limitType
    ): string {
        // Parse base time
        $time = Carbon::createFromFormat('H:i:s', strlen($baseTime) === 5 ? $baseTime.':00' : $baseTime);

        // Generate random offset dalam menit
        $randomMinutes = rand($minMinutes, $maxMinutes);

        // Generate random detik (0-59)
        $randomSeconds = rand(0, 59);

        // Apply offset
        $randomTime = $time->copy()->addMinutes($randomMinutes)->seconds($randomSeconds);

        // Parse limit time
        $limit = Carbon::createFromFormat('H:i:s', strlen($limitTime) === 5 ? $limitTime.':00' : $limitTime);

        // Apply constraint
        if ($limitType === 'max' && $randomTime->gt($limit)) {
            return $limit->format('H:i:s');
        } elseif ($limitType === 'min' && $randomTime->lt($limit)) {
            return $limit->format('H:i:s');
        }

        return $randomTime->format('H:i:s');
    }

    /**
     * Batch insert dengan chunking untuk menghindari query terlalu besar
     */
    private function batchInsert(array $dataToInsert): int
    {
        $totalInserted = 0;

        foreach (array_chunk($dataToInsert, self::DATABASE_BATCH_SIZE) as $chunk) {
            DB::table('presensi_siswas')->insert($chunk);
            $totalInserted += count($chunk);
        }

        return $totalInserted;
    }

    /**
     * Send notification to user
     */
    private function sendNotification(string $title, string $body, string $status = 'success'): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body);

        match ($status) {
            'success' => $notification->success(),
            'danger' => $notification->danger(),
            'warning' => $notification->warning(),
            default => $notification->info(),
        };

        $notification->sendToDatabase($user);
    }
}
