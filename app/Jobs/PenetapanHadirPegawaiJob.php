<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Pegawai;
use App\Models\Instansi;
use App\Enums\StatusPulang;
use App\Enums\StatusPresensi;
use App\Models\PresensiPegawai;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PenetapanHadirPegawaiJob implements ShouldQueue
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
            $pegawaiIds = [$this->data['namaPegawai']];
        } elseif ($this->data['tipe'] === 'all') {
            $pegawaiIds = Pegawai::where('status', true)->pluck('id')->toArray();
        } elseif ($this->data['tipe'] === 'jabatan') {
            $pegawaiIds = Pegawai::whereHas('jabatan', function ($query) {
                $query->whereIn('jabatan_id', $this->data['jabatan']);
            })->pluck('id')->toArray();
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
                        continue; // skip
                    }
                } elseif ($instansi->status === 'Swasta') {
                    if ($carbonDate->isSaturday()) {
                        continue; // skip
                    }
                }

                $sudahAda = PresensiPegawai::where('pegawai_id', $pegawaiId)
                    ->whereDate('tanggal', $tanggal)
                    ->exists();

                if (!$sudahAda) {
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

        // Kirim notifikasi setelah job selesai
        Notification::make()
            ->title('Penetapan Hadir Selesai')
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
            ->title('Penetapan Hadir Gagal')
            ->body('Terjadi kesalahan saat memproses data presensi.')
            ->danger()
            ->send();
    }
}
