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

    public $timeout = 600; // 10 menit

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
        $jamDatang = $this->data['jamDatang'] ?? null;
        $jamPulang = $this->data['jamPulang'] ?? null;

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
            $jamDatang,
            $jamPulang,
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
        ?string $jamDatang,
        ?string $jamPulang,
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
