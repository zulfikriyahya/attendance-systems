<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Siswa;
use App\Enums\StatusPresensi;
use App\Models\PresensiSiswa;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SetLiburSiswa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $failOnTimeout = true;

    public function __construct(
        public array $data,
        public int $userId
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $tanggalMulai = Carbon::parse($this->data['tanggalMulai']);
        $tanggalSelesai = Carbon::parse($this->data['tanggalSelesai']);
        $catatan = $this->data['catatan'];

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
            })->where('status', true)->pluck('id')->toArray();
        } else {
            $siswaIds = [];
        }

        foreach ($siswaIds as $siswaId) {
            foreach ($rangeTanggal as $tanggal) {
                $sudahAda = PresensiSiswa::where('siswa_id', $siswaId)
                    ->whereDate('tanggal', $tanggal)
                    ->exists();

                if (! $sudahAda) {
                    PresensiSiswa::create([
                        'siswa_id' => $siswaId,
                        'tanggal' => $tanggal,
                        'statusPresensi' => StatusPresensi::Libur->value,
                        'catatan' => $catatan,
                    ]);
                    $jumlahBerhasil++;
                } else {
                    $jumlahDiabaikan++;
                }
            }
        }

        $user = User::find($this->userId);
        if ($user) {
            Notification::make()
                ->title('Penetapan Libur Selesai')
                ->body("🟢 {$jumlahBerhasil} data berhasil disimpan. 🔴 {$jumlahDiabaikan} data diabaikan.")
                ->success()
                ->sendToDatabase($user);
        }
    }
}
