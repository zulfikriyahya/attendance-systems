<?php

namespace App\Http\Controllers;

use App\Models\JadwalPresensi;
use App\Models\Pegawai;
use App\Models\PresensiPegawai;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Services\PresensiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// With Cache
class PresensiController extends Controller
{
    /**
     * Get cache TTL sampai akhir hari (dalam detik)
     */
    private function getCacheTTLUntilEndOfDay(): int
    {
        return now()->endOfDay()->diffInSeconds(now());
    }

    // Cache TTL constants untuk data yang tidak berubah per hari
    public const CACHE_RFID_TTL = 86400; // 24 jam - data RFID jarang berubah

    public const CACHE_HEALTH_TTL = 30; // 30 detik - health check tetap pendek

    public function getJadwalHariIni()
    {
        $now = now();
        $cacheKey = 'jadwal_presensi_'.$now->toDateString();

        $jadwal = Cache::remember($cacheKey, $this->getCacheTTLUntilEndOfDay(), function () use ($now) {
            return JadwalPresensi::where('status', true)
                ->where('hari', $now->isoFormat('dddd'))
                ->first();
        });

        if (! $jadwal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada jadwal presensi untuk hari ini',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Jadwal presensi ditemukan',
            'data' => [
                'hari' => $jadwal->hari,
                'jam_datang' => $jadwal->jamDatang,
                'jam_pulang' => $jadwal->jamPulang,
                'tanggal' => $now->toDateString(),
                'waktu_sekarang' => $now->format('H:i:s'),
            ],
        ]);
    }

    public function getStatusPresensi($rfid)
    {
        $today = now()->toDateString();
        $cacheKey = "status_presensi_{$rfid}_{$today}";

        $result = Cache::remember($cacheKey, $this->getCacheTTLUntilEndOfDay(), function () use ($rfid, $today) {
            $buildData = fn ($user, $type, $presensi) => [
                'status' => 'success',
                'message' => 'Status presensi ditemukan',
                'data' => [
                    'type' => $type,
                    'nama' => $user->user?->name ?? $user->nama,
                    'rfid' => $rfid,
                    'presensi_hari_ini' => $presensi ? [
                        'jam_datang' => $presensi->jamDatang,
                        'jam_pulang' => $presensi->jamPulang,
                        'status' => $presensi->statusPresensi?->label(),
                        'tanggal' => $presensi->tanggal,
                    ] : null,
                    'sudah_presensi_masuk' => (bool) $presensi,
                    'sudah_presensi_pulang' => $presensi && $presensi->jamPulang !== null,
                ],
            ];

            if ($pegawai = Pegawai::where('rfid', $rfid)->first()) {
                $presensi = PresensiPegawai::where('pegawai_id', $pegawai->id)
                    ->whereDate('tanggal', $today)
                    ->first();

                return ['response' => $buildData($pegawai, 'pegawai', $presensi), 'found' => true];
            }

            if ($siswa = Siswa::where('rfid', $rfid)->first()) {
                $presensi = PresensiSiswa::where('siswa_id', $siswa->id)
                    ->whereDate('tanggal', $today)
                    ->first();

                return ['response' => $buildData($siswa, 'siswa', $presensi), 'found' => true];
            }

            return ['found' => false];
        });

        if (! $result['found']) {
            return response()->json([
                'status' => 'error',
                'message' => 'RFID tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json($result['response']);
    }

    public function validateRfid(Request $request)
    {
        $request->validate([
            'rfid' => 'required|string|size:10',
        ]);

        $rfid = $request->input('rfid');

        if (! $rfid) {
            return response()->json([
                'status' => 'error',
                'message' => 'RFID tidak boleh kosong',
            ], 400);
        }

        $cacheKey = "rfid_validation_{$rfid}";

        $result = Cache::remember($cacheKey, self::CACHE_RFID_TTL, function () use ($rfid) {
            if (Pegawai::where('rfid', $rfid)->exists()) {
                return ['valid' => true, 'type' => 'pegawai'];
            }
            if (Siswa::where('rfid', $rfid)->exists()) {
                return ['valid' => true, 'type' => 'siswa'];
            }

            return ['valid' => false];
        });

        if (! $result['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => 'RFID tidak dikenali',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Valid '.ucfirst($result['type']),
        ]);
    }

    public function store(Request $request, PresensiService $service)
    {
        $request->validate([
            'rfid' => 'required|string|size:10',
            'sync_mode' => 'nullable|boolean',
            'timestamp' => 'nullable|date',
            'device_id' => 'nullable|string',
        ]);

        $result = $service->prosesPresensi(
            $request->rfid,
            $request->timestamp,
            $request->boolean('sync_mode'),
            $request->device_id
        );

        // Clear cache setelah presensi berhasil
        if ($result['status'] === 'success') {
            $this->clearPresensiCache($request->rfid);
        }

        return response()->json($result, $result['status'] === 'success' ? 200 : 400);
    }

    public function syncBulk(Request $request, PresensiService $service)
    {
        $request->validate([
            'data' => 'required|array',
            'data.*.rfid' => 'required|string|size:10',
            'data.*.timestamp' => 'required|date',
            'data.*.device_id' => 'required|string',
        ]);

        $results = collect($request->data)->map(function ($item) use ($service) {
            $result = $service->prosesPresensi(
                $item['rfid'],
                $item['timestamp'],
                true,
                $item['device_id']
            );

            // Clear cache untuk setiap RFID yang berhasil
            if ($result['status'] === 'success') {
                $this->clearPresensiCache($item['rfid']);
            }

            return $result + ['rfid' => $item['rfid'], 'timestamp' => $item['timestamp']];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk sync selesai',
            'data' => $results,
        ]);
    }

    public function health()
    {
        $cacheKey = 'system_health_check';

        try {
            $healthData = Cache::remember($cacheKey, self::CACHE_HEALTH_TTL, function () {
                DB::connection()->getPdo();

                $tablesExist = DB::getSchemaBuilder()->hasTable('presensi_pegawais') &&
                    DB::getSchemaBuilder()->hasTable('presensi_siswas') &&
                    DB::getSchemaBuilder()->hasTable('jadwal_presensis');

                if (! $tablesExist) {
                    throw new \Exception('Database tables not found');
                }

                $jadwalHariIni = JadwalPresensi::where('status', true)
                    ->where('hari', now()->isoFormat('dddd'))
                    ->exists();

                return [
                    'database_status' => 'connected',
                    'jadwal_hari_ini' => $jadwalHariIni,
                    'tables_exist' => true,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'API is healthy',
                'data' => [
                    'server_time' => now()->toISOString(),
                    'server_timezone' => config('app.timezone'),
                    'database_status' => $healthData['database_status'],
                    'jadwal_hari_ini' => $healthData['jadwal_hari_ini'],
                    'api_version' => '1.0',
                    'cache_enabled' => true,
                ],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 503);
        }
    }

    public function deviceStats(Request $request)
    {
        $deviceId = $request->input('device_id', 'unknown');
        $hours = $request->input('hours', 24);

        $cacheKey = "device_stats_{$deviceId}_{$hours}";

        try {
            $stats = Cache::remember($cacheKey, $this->getCacheTTLUntilEndOfDay(), function () use ($hours) {
                $startTime = now()->subHours($hours);

                $presensiPegawai = PresensiPegawai::where('created_at', '>=', $startTime)->count();
                $presensiSiswa = PresensiSiswa::where('created_at', '>=', $startTime)->count();
                $syncedPegawai = PresensiPegawai::where('created_at', '>=', $startTime)
                    ->where('is_synced', true)->count();
                $syncedSiswa = PresensiSiswa::where('created_at', '>=', $startTime)
                    ->where('is_synced', true)->count();

                $totalPresensi = $presensiPegawai + $presensiSiswa;
                $totalSynced = $syncedPegawai + $syncedSiswa;

                return [
                    'start_time' => $startTime->toISOString(),
                    'total_presensi' => $totalPresensi,
                    'presensi_pegawai' => $presensiPegawai,
                    'presensi_siswa' => $presensiSiswa,
                    'synced_records' => $totalSynced,
                    'sync_rate' => $totalPresensi > 0
                        ? round(($totalSynced / $totalPresensi) * 100, 2)
                        : 0,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Device statistics retrieved',
                'data' => [
                    'device_id' => $deviceId,
                    'period_hours' => $hours,
                    'start_time' => $stats['start_time'],
                    'end_time' => now()->toISOString(),
                    'total_presensi' => $stats['total_presensi'],
                    'presensi_pegawai' => $stats['presensi_pegawai'],
                    'presensi_siswa' => $stats['presensi_siswa'],
                    'synced_records' => $stats['synced_records'],
                    'sync_rate' => $stats['sync_rate'],
                ],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Device stats failed', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get device statistics',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Clear cache yang berkaitan dengan presensi RFID tertentu
     */
    private function clearPresensiCache(string $rfid): void
    {
        $today = now()->toDateString();

        // Clear cache status presensi
        Cache::forget("status_presensi_{$rfid}_{$today}");

        // Clear cache validasi RFID (opsional, karena data RFID jarang berubah)
        // Cache::forget("rfid_validation_{$rfid}");

        // Clear cache stats jika diperlukan
        Cache::forget('device_stats_unknown_24');
    }

    /**
     * Method tambahan untuk clear semua cache presensi (untuk admin)
     */
    public function clearAllCache()
    {
        try {
            Cache::tags(['presensi'])->flush();

            // Atau clear specific patterns
            Cache::forget('jadwal_presensi_'.now()->toDateString());
            Cache::forget('system_health_check');

            return response()->json([
                'status' => 'success',
                'message' => 'Cache berhasil dibersihkan',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membersihkan cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
