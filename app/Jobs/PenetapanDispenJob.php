<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Siswa;
use App\Models\Instansi;
use App\Enums\StatusPulang;
use App\Enums\StatusPresensi;
use App\Models\PresensiSiswa;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PenetapanDispenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
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
        $rangeTanggal = collect();

        for ($date = $tanggalMulai->copy(); $date->lte($tanggalSelesai); $date->addDay()) {
            $rangeTanggal->push($date->format('Y-m-d'));
        }

        $jumlahBerhasil = 0;
        $jumlahDiabaikan = 0;

        if ($this->data['tipe'] === 'single') {
            $siswaIds = [$this->data['namaSiswa']];
        } elseif ($this->data['tipe'] === 'all') {
            $siswaIds = Siswa::where('status', true)->pluck('id')->toArray();
        } elseif ($this->data['tipe'] === 'jabatan') {
            $siswaIds = Siswa::whereHas('jabatan', function ($query) {
                $query->whereIn('jabatan_id', $this->data['jabatan']);
            })->pluck('id')->toArray();
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
                        continue; // skip
                    }
                } elseif ($instansi->status === 'Swasta') {
                    if ($carbonDate->isSaturday()) {
                        continue; // skip
                    }
                }

                $sudahAda = PresensiSiswa::where('siswa_id', $siswaId)
                    ->whereDate('tanggal', $tanggal)
                    ->exists();

                if (!$sudahAda) {
                    PresensiSiswa::create([
                        'siswa_id' => $siswaId,
                        'tanggal' => $tanggal,
                        'statusPresensi' => StatusPresensi::Dispen->value,
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

        // Kirim notifikasi setelah job selesai
        Notification::make()
            ->title('Penetapan Dispen Selesai')
            ->body("🟢 {$jumlahBerhasil} data berhasil disimpan. 🔴 {$jumlahDiabaikan} data diabaikan.")
            ->success()
            ->send();
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Notification::make()
            ->title('Penetapan Dispen Gagal')
            ->body('Terjadi kesalahan saat memproses data presensi.')
            ->danger()
            ->send();
    }
}
