<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Instansi;
use App\Enums\StatusPulang;
use App\Enums\StatusPresensi;
use Illuminate\Bus\Queueable;
use App\Models\PresensiPegawai;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SetHadirPegawai implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 3600; // 60 menit

    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public int $userId // User yang melakukan action
    ) {
        $this->onQueue('default'); // Queue berbeda dari whatsapp
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tanggalMulai = Carbon::parse($this->data['tanggalMulai']);
        $tanggalSelesai = Carbon::parse($this->data['tanggalSelesai']);
        $catatan = $this->data['catatan'];
        $jamDatangBase = $this->data['jamDatang'];
        $jamPulangBase = $this->data['jamPulang'];

        // Generate range tanggal
        $rangeTanggal = collect();
        for ($date = $tanggalMulai->copy(); $date->lte($tanggalSelesai); $date->addDay()) {
            $rangeTanggal->push($date->format('Y-m-d'));
        }

        $jumlahBerhasil = 0;
        $jumlahDiabaikan = 0;

        // Tentukan pegawai IDs
        if ($this->data['tipe'] === 'single') {
            $pegawaiIds = [$this->data['namaPegawai']];
        } elseif ($this->data['tipe'] === 'all') {
            $pegawaiIds = Pegawai::where('status', true)->pluck('id')->toArray();
        } elseif ($this->data['tipe'] === 'jabatan') {
            $pegawaiIds = Pegawai::whereHas('jabatan', function ($query) {
                $query->whereIn('jabatan_id', $this->data['jabatan']);
            })->where('status', true)->pluck('id')->toArray();
        } else {
            $pegawaiIds = [];
        }

        $instansi = Instansi::first();

        foreach ($pegawaiIds as $pegawaiId) {
            foreach ($rangeTanggal as $tanggal) {
                $carbonDate = Carbon::parse($tanggal);

                // Cek pengecualian hari
                if ($instansi->status === 'Negeri') {
                    if ($carbonDate->isSaturday() || $carbonDate->isSunday()) {
                        continue; // skip weekend
                    }
                } elseif ($instansi->status === 'Swasta') {
                    if ($carbonDate->isSunday()) {
                        continue; // skip Minggu
                    }
                }

                $sudahAda = PresensiPegawai::where('pegawai_id', $pegawaiId)
                    ->whereDate('tanggal', $tanggal)
                    ->exists();

                if (! $sudahAda) {
                    // Generate random jam datang (±15 menit, maksimal 07:00)
                    $jamDatang = $this->generateRandomTime($jamDatangBase, -15, 15, '07:00', 'max');
                    
                    // Generate random jam pulang (±15 menit, minimal 16:00)
                    $jamPulang = $this->generateRandomTime($jamPulangBase, -15, 15, '16:00', 'min');

                    PresensiPegawai::create([
                        'pegawai_id' => $pegawaiId,
                        'tanggal' => $tanggal,
                        'statusPresensi' => StatusPresensi::Hadir->value,
                        'statusPulang' => StatusPulang::Pulang->value,
                        'jamDatang' => $jamDatang,
                        'jamPulang' => $jamPulang,
                        'catatan' => $catatan,
                    ]);
                    $jumlahBerhasil++;
                } else {
                    $jumlahDiabaikan++;
                }
            }
        }

        // Kirim notifikasi ke user yang melakukan action
        $user = User::find($this->userId);
        if ($user) {
            Notification::make()
                ->title('Penetapan Hadir Selesai')
                ->body("🟢 {$jumlahBerhasil} data berhasil disimpan. 🔴 {$jumlahDiabaikan} data diabaikan.")
                ->success()
                ->sendToDatabase($user);
        }
    }

    /**
     * Generate random time dengan constraint (termasuk detik random)
     * 
     * @param string $baseTime Jam dasar (format HH:mm:ss atau HH:mm)
     * @param int $minMinutes Offset minimal dalam menit (bisa negatif)
     * @param int $maxMinutes Offset maksimal dalam menit
     * @param string $limitTime Jam batas (format HH:mm:ss atau HH:mm)
     * @param string $limitType 'max' atau 'min'
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
        $time = Carbon::createFromFormat('H:i:s', strlen($baseTime) === 5 ? $baseTime . ':00' : $baseTime);
        
        // Generate random offset dalam menit
        $randomMinutes = rand($minMinutes, $maxMinutes);
        
        // Generate random detik (0-59)
        $randomSeconds = rand(0, 59);
        
        // Apply offset
        $randomTime = $time->copy()->addMinutes($randomMinutes)->seconds($randomSeconds);
        
        // Parse limit time
        $limit = Carbon::createFromFormat('H:i:s', strlen($limitTime) === 5 ? $limitTime . ':00' : $limitTime);
        
        // Apply constraint
        if ($limitType === 'max' && $randomTime->gt($limit)) {
            return $limit->format('H:i:s');
        } elseif ($limitType === 'min' && $randomTime->lt($limit)) {
            return $limit->format('H:i:s');
        }
        
        return $randomTime->format('H:i:s');
    }
}