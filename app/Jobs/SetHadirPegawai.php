<?php

namespace App\Jobs;

use App\Enums\StatusPresensi;
use App\Enums\StatusPulang;
use App\Models\Instansi;
use App\Models\Pegawai;
use App\Models\PresensiPegawai;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SetHadirPegawai implements ShouldQueue
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
}
