<?php

namespace App\Jobs;

use App\Enums\StatusPulang;
use App\Enums\StatusPresensi;
use App\Models\Instansi;
use App\Models\Siswa;
use App\Models\PresensiSiswa;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SetHadirSiswa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 menit
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
        $jamDatang = $this->data['jamDatang'];
        $jamPulang = $this->data['jamPulang'];

        // Generate range tanggal
        $rangeTanggal = collect();
        for ($date = $tanggalMulai->copy(); $date->lte($tanggalSelesai); $date->addDay()) {
            $rangeTanggal->push($date->format('Y-m-d'));
        }

        $jumlahBerhasil = 0;
        $jumlahDiabaikan = 0;

        // Tentukan siswa IDs
        if ($this->data['tipe'] === 'single') {
            $siswaIds = [$this->data['namaSiswa']];
        } elseif ($this->data['tipe'] === 'all') {
            $siswaIds = Siswa::where('status', true)->pluck('id')->toArray();
        } elseif ($this->data['tipe'] === 'jabatan') {
            $siswaIds = Siswa::whereHas('jabatan', function ($query) {
                $query->whereIn('jabatan_id', $this->data['jabatan']);
            })->where('status', true)->pluck('id')->toArray();
        } else {
            $siswaIds = [];
        }

        $instansi = Instansi::first();

        foreach ($siswaIds as $siswaId) {
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

                $sudahAda = PresensiSiswa::where('siswa_id', $siswaId)
                    ->whereDate('tanggal', $tanggal)
                    ->exists();

                if (!$sudahAda) {
                    PresensiSiswa::create([
                        'siswa_id' => $siswaId,
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

        logger()->info('Set Hadir Siswa completed', [
            'user_id' => $this->userId,
            'berhasil' => $jumlahBerhasil,
            'diabaikan' => $jumlahDiabaikan,
            'total_siswa' => count($siswaIds),
            'periode' => "{$tanggalMulai->format('Y-m-d')} - {$tanggalSelesai->format('Y-m-d')}",
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        logger()->error('Set Hadir Siswa job failed', [
            'user_id' => $this->userId,
            'data' => $this->data,
            'error' => $exception->getMessage(),
        ]);

        // Notifikasi ke user bahwa job gagal
        $user = User::find($this->userId);
        if ($user) {
            Notification::make()
                ->title('Penetapan Hadir Gagal')
                ->body('❌ Terjadi kesalahan saat memproses penetapan hadir. Silakan coba lagi.')
                ->danger()
                ->sendToDatabase($user);
        }
    }
}
