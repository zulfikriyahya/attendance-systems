<?php

namespace App\Jobs;

use App\Enums\StatusPresensi;
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

class SetCutiPegawai implements ShouldQueue
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

        foreach ($pegawaiIds as $pegawaiId) {
            foreach ($rangeTanggal as $tanggal) {
                $sudahAda = PresensiPegawai::where('pegawai_id', $pegawaiId)
                    ->whereDate('tanggal', $tanggal)
                    ->exists();

                if (! $sudahAda) {
                    PresensiPegawai::create([
                        'pegawai_id' => $pegawaiId,
                        'tanggal' => $tanggal,
                        'statusPresensi' => StatusPresensi::Cuti->value,
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
                ->title('Penetapan Cuti Selesai')
                ->body("🟢 {$jumlahBerhasil} data berhasil disimpan. 🔴 {$jumlahDiabaikan} data diabaikan.")
                ->success()
                ->sendToDatabase($user);
        }

        logger()->info('Set Cuti Pegawai completed', [
            'user_id' => $this->userId,
            'berhasil' => $jumlahBerhasil,
            'diabaikan' => $jumlahDiabaikan,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('Set Cuti Pegawai job failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        $user = User::find($this->userId);
        if ($user) {
            Notification::make()
                ->title('Penetapan Cuti Gagal')
                ->body('❌ Terjadi kesalahan. Silakan coba lagi.')
                ->danger()
                ->sendToDatabase($user);
        }
    }
}
