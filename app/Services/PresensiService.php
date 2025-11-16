<?php

namespace App\Services;

use App\Enums\StatusPresensi;
use App\Enums\StatusPulang;
use App\Jobs\SendWhatsappMessage;
use App\Models\JadwalPresensi;
use App\Models\Pegawai;
use App\Models\PresensiPegawai;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PresensiService
{
    protected WhatsappDelayService $delayService;

    public function __construct(WhatsappDelayService $delayService)
    {
        $this->delayService = $delayService;
    }

    public function prosesPresensi(string $rfid, ?string $timestamp = null, bool $isSync = false, ?string $deviceId = null): array
    {
        $now = $timestamp ? Carbon::parse($timestamp) : now();
        $today = $now->toDateString();
        $nowTime = $now->format('H:i:s');
        $hariIni = $now->isoFormat('dddd');

        return DB::transaction(function () use ($rfid, $now, $today, $nowTime, $hariIni, $isSync) {
            foreach ([Pegawai::class => false, Siswa::class => true] as $model => $isSiswa) {

                // Cari user dengan RFID
                $user = $model::with('jabatan.jadwalPresensis', 'jabatan.instansi')
                    ->where('rfid', $rfid)
                    ->first();

                if (! $user) {
                    continue;
                }

                // Ambil jadwal hari ini dari cache
                $jadwalHariIni = Cache::remember(
                    "jadwal_presensi:{$hariIni}",
                    now()->addMinutes(10),
                    fn () => JadwalPresensi::where('status', true)
                        ->where('hari', $hariIni)
                        ->with('jabatans:id')
                        ->get()
                        ->flatMap(
                            fn ($jadwal) => $jadwal->jabatans->mapWithKeys(
                                fn ($jabatan) => [(string) $jabatan->id => collect([$jadwal])]
                            )
                        )
                );

                $jadwal = optional($jadwalHariIni->get((string) $user->jabatan_id))->first();

                if (! $jadwal) {
                    return [
                        'status' => 'error',
                        'message' => 'Tidak ada jadwal presensi untuk hari ini',
                    ];
                }

                $presensiModel = $isSiswa ? PresensiSiswa::class : PresensiPegawai::class;
                $field = $isSiswa ? 'siswa_id' : 'pegawai_id';
                $presensi = $presensiModel::where($field, $user->id)
                    ->whereDate('tanggal', $today)
                    ->first();

                $userModel = $user->user;
                $nama = $userModel?->name ?? $user->nama ?? 'Tidak dikenal';
                $instansi = $userModel?->instansi_name ?? 'Instansi';

                // Presensi Masuk
                if (! $presensi) {
                    $status = $nowTime <= $jadwal->jamDatang
                        ? StatusPresensi::Hadir
                        : StatusPresensi::Terlambat;

                    $presensiModel::create([
                        $field => $user->id,
                        'tanggal' => $today,
                        'jamDatang' => $nowTime,
                        'statusPresensi' => $status,
                        'is_synced' => $isSync,
                        'synced_at' => $isSync ? now() : null,
                    ]);

                    $this->sendNotif($user->telepon, 'Presensi Masuk', $status->label(), $nowTime, $nama, $isSiswa, $instansi, $isSync);

                    return [
                        'status' => 'success',
                        'message' => "Presensi masuk berhasil sebagai {$status->label()}",
                        'data' => compact('nama', 'nowTime', 'isSync') + ['status' => $status->value],
                    ];
                }

                // Presensi Pulang
                if ($presensi->jamPulang) {
                    return [
                        'status' => 'error',
                        'message' => 'Anda sudah presensi masuk dan pulang hari ini',
                    ];
                }

                if (! $isSync && $now->lt(Carbon::createFromTimeString($presensi->jamDatang)->addMinutes(30))) {
                    return [
                        'status' => 'error',
                        'message' => 'Presensi kedua hanya diizinkan setelah 30 menit',
                    ];
                }

                $statusPulang = $nowTime <= $jadwal->jamPulang
                    ? StatusPulang::PulangCepat
                    : StatusPulang::Pulang;

                $presensi->update([
                    'jamPulang' => $nowTime,
                    'statusPulang' => $statusPulang,
                    'is_synced' => $isSync,
                    'synced_at' => $isSync ? now() : null,
                ]);

                $this->sendNotif($user->telepon, 'Presensi Pulang', $statusPulang->label(), $nowTime, $nama, $isSiswa, $instansi, $isSync);

                return [
                    'status' => 'success',
                    'message' => 'Presensi pulang berhasil',
                    'data' => compact('nama', 'nowTime', 'isSync') + ['status' => $statusPulang->value],
                ];
            }

            return ['status' => 'error', 'message' => 'RFID tidak dikenal'];
        });
    }

    private function sendNotif(
        string $telepon,
        string $jenis,
        string $status,
        string $jam,
        string $nama,
        bool $isSiswa,
        string $instansi,
        bool $isSync
    ): void {
        if (! $isSync) {
            $delay = $this->delayService->calculateRealtimeDelay($status);

            // Dispatch unified job
            SendWhatsappMessage::dispatch(
                $telepon,
                'presensi', // type
                [
                    'jenis' => $jenis,
                    'status' => $status,
                    'waktu' => $jam,
                    'nama' => $nama,
                    'isSiswa' => $isSiswa,
                    'instansi' => $instansi,
                ]
            )->delay($delay);
        }
    }
}
