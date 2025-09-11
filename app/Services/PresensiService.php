<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Siswa;
use App\Models\Pegawai;
use App\Enums\StatusPulang;
use App\Enums\StatusPresensi;
use App\Models\PresensiSiswa;
use App\Models\JadwalPresensi;
use App\Models\PresensiPegawai;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendWhatsappNotification;

class PresensiService
{
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

                $nama = $user->user?->name ?? $user->nama ?? 'Tidak dikenal';
                $instansi = $user->jabatan?->instansi?->nama ?? 'Instansi';

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

    // private function sendNotif(
    //     string $telepon,
    //     string $jenis,
    //     string $status,
    //     string $jam,
    //     string $nama,
    //     bool $isSiswa,
    //     string $instansi,
    //     bool $isSync
    // ): void {
    //     if (! $isSync) {
    //         $cacheKey = 'whatsapp_notif_count_'.now()->format('Y-m-d');
    //         $notifCount = Cache::get($cacheKey, 0);
    //         $minuteSlot = floor($notifCount / 10);
    //         $baseDelay = rand(5, 15);
    //         $slotDelay = $minuteSlot * 60;
    //         $randomSpread = rand(0, 30);

    //         $totalDelay = $baseDelay + $slotDelay + $randomSpread;

    //         $maxDelay = 60 * 60;
    //         $totalDelay = min($totalDelay, $maxDelay);

    //         $delay = now()->addSeconds($totalDelay);
    //         SendWhatsappNotification::dispatch(
    //             $telepon,
    //             $jenis,
    //             $status,
    //             $jam,
    //             $nama,
    //             $isSiswa,
    //             $instansi
    //         )->delay($delay);

    //         $newCount = $notifCount + 1;
    //         Cache::put($cacheKey, $newCount, now()->endOfDay());
    //     }
    // }

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
        if (!$isSync) {
            $now = now();
            $today = $now->format('Y-m-d');
            $currentHour = $now->format('H');
            $currentMinute = $now->format('i');

            // Cache key per jam untuk reset otomatis setiap jam
            $hourlyCacheKey = "whatsapp_hourly_{$today}_{$currentHour}";

            // Hitung jumlah notifikasi dalam jam ini
            $hourlyCount = Cache::get($hourlyCacheKey, 0);

            // Strategi distribusi untuk 1100 siswa
            // Target: maksimal 30 menit delay, aman dari banned

            // WhatsApp rate limit yang aman: ~40-50 pesan per menit
            // Untuk 1100 siswa dalam 30 menit = perlu ~37 pesan/menit
            $messagesPerMinute = 35; // Rate yang aman
            $maxDelayMinutes = 30;   // Maksimal 30 menit

            // Hitung slot berdasarkan urutan dalam jam ini
            $minuteSlot = floor($hourlyCount / $messagesPerMinute);

            // Jika sudah melewati 30 menit, reset ke awal dengan jeda kecil
            if ($minuteSlot >= $maxDelayMinutes) {
                $minuteSlot = $minuteSlot % $maxDelayMinutes;
                // Tambah offset kecil untuk menghindari collision
                $extraOffset = floor($hourlyCount / ($messagesPerMinute * $maxDelayMinutes)) * 60;
            } else {
                $extraOffset = 0;
            }

            // Priority system untuk status tertentu
            $isPriority = in_array($status, ['Terlambat', 'Pulang Cepat']);

            if ($isPriority) {
                // Priority: delay minimal (0-2 menit)
                $baseDelaySeconds = rand(10, 120);
                $slotDelaySeconds = min($minuteSlot * 30, 300); // Max 5 menit untuk priority
            } else {
                // Normal: distribusi merata dalam 30 menit
                $baseDelaySeconds = rand(15, 45);
                $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
            }

            // Random spread untuk distribusi natural
            $randomSpread = rand(0, 30);

            // Total delay dalam detik
            $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $extraOffset;

            // Pastikan tidak melebihi 30 menit (1800 detik)
            $maxDelaySeconds = $maxDelayMinutes * 60;
            $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

            // Untuk debug/monitoring - bisa dihapus di production
            Log::info("WhatsApp Delay Calculation", [
                'hourly_count' => $hourlyCount,
                'minute_slot' => $minuteSlot,
                'is_priority' => $isPriority,
                'total_delay_seconds' => $totalDelaySeconds,
                'total_delay_minutes' => round($totalDelaySeconds / 60, 2)
            ]);

            $delay = $now->addSeconds($totalDelaySeconds);

            // Dispatch notification
            SendWhatsappNotification::dispatch(
                $telepon,
                $jenis,
                $status,
                $jam,
                $nama,
                $isSiswa,
                $instansi
            )->delay($delay);

            // Update counter dengan expire otomatis di akhir jam
            $newCount = $hourlyCount + 1;
            Cache::put($hourlyCacheKey, $newCount, now()->endOfHour());

            // Optional: Tracking untuk monitoring
            $this->trackNotificationStats($today, $currentHour, $jenis, $status);
        }
    }

    /**
     * Tracking untuk monitoring dan debugging
     */
    private function trackNotificationStats(string $today, string $currentHour, string $jenis, string $status): void
    {
        $statsKey = "whatsapp_stats_{$today}_{$currentHour}";
        $stats = Cache::get($statsKey, [
            'total' => 0,
            'masuk' => 0,
            'pulang' => 0,
            'hadir' => 0,
            'terlambat' => 0,
            'pulang_cepat' => 0,
            'pulang_normal' => 0,
        ]);

        $stats['total']++;
        $stats[$jenis === 'Presensi Masuk' ? 'masuk' : 'pulang']++;

        switch ($status) {
            case 'Hadir':
                $stats['hadir']++;
                break;
            case 'Terlambat':
                $stats['terlambat']++;
                break;
            case 'Pulang Cepat':
                $stats['pulang_cepat']++;
                break;
            case 'Pulang':
                $stats['pulang_normal']++;
                break;
        }

        Cache::put($statsKey, $stats, now()->endOfHour());
    }

    /**
     * Method untuk mendapatkan statistik notifikasi (opsional)
     * Berguna untuk monitoring di dashboard admin
     */
    public function getNotificationStats(): array
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $currentHour = $now->format('H');

        $hourlyKey = "whatsapp_hourly_{$today}_{$currentHour}";
        $statsKey = "whatsapp_stats_{$today}_{$currentHour}";

        $hourlyCount = Cache::get($hourlyKey, 0);
        $stats = Cache::get($statsKey, []);

        // Estimasi waktu delay berikutnya
        $messagesPerMinute = 35;
        $nextDelayMinutes = floor($hourlyCount / $messagesPerMinute);

        return [
            'hourly_count' => $hourlyCount,
            'next_delay_minutes' => min($nextDelayMinutes, 30),
            'queue_health' => DB::table('jobs')->where('queue', 'default')->count(),
            'failed_today' => DB::table('failed_jobs')->whereDate('failed_at', $today)->count(),
            'stats' => $stats,
            'rate_per_minute' => $messagesPerMinute,
            'max_delay_minutes' => 30,
        ];
    }
}
