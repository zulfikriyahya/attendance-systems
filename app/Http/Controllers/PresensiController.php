<?php

namespace App\Http\Controllers;

use App\Models\JadwalPresensi;
use App\Models\Pegawai;
use App\Models\PresensiPegawai;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Services\PresensiService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresensiController extends Controller
{
    public function __construct(protected WhatsappService $whatsapp) {}

    protected function getNamaInstansi(Pegawai|Siswa $user): string
    {
        return collect([$user->jabatan?->instansi?->nama])
            ->filter()
            ->unique()
            ->implode(', ') ?: 'Instansi';
    }

    protected function sendNotifikasiWa(?string $nomor, string $jenis, string $status, string $waktu, string $nama, bool $isSiswa, string $instansi): void
    {
        if (! $nomor) {
            return;
        }
        $ikon = $jenis === 'Presensi Masuk' ? '📥' : '📤';
        $tanggal = now()->translatedFormat('d F Y');
        $penutup = $isSiswa
            ? ($jenis === 'Presensi Masuk' ? 'Semangat belajar! 📚' : 'Sampai jumpa besok 👋')
            : ($jenis === 'Presensi Masuk' ? 'Selamat bekerja! 💼' : 'Terima kasih atas kinerjanya 🙌');
        $pesan = <<<TEXT
        *Presensi Online (POL)*
    
        *{$ikon} {$jenis}*
        Nama    : {$nama}
        Status  : *{$status}*
        Tanggal : {$tanggal}
        Waktu   : {$waktu} WIB
    
        {$penutup}
        *{$instansi}*

        _*Informasi lebih lanjut dapat diakses melalui link berikut :*_
        https://drive.mtsn1pandeglang.sch.id/
        TEXT;
        try {
            $this->whatsapp->send($nomor, $pesan);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification', [
                'nomor' => $nomor,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getJadwalHariIni()
    {
        $now = now();
        $jadwal = JadwalPresensi::where('status', true)
            ->where('hari', $now->isoFormat('dddd'))
            ->first();
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
            $presensi = PresensiPegawai::where('pegawai_id', $pegawai->id)->whereDate('tanggal', $today)->first();

            return response()->json($buildData($pegawai, 'pegawai', $presensi));
        }
        if ($siswa = Siswa::where('rfid', $rfid)->first()) {
            $presensi = PresensiSiswa::where('siswa_id', $siswa->id)->whereDate('tanggal', $today)->first();

            return response()->json($buildData($siswa, 'siswa', $presensi));
        }

        return response()->json(['status' => 'error', 'message' => 'RFID tidak ditemukan', 'data' => null], 404);
    }

    public function validateRfid(Request $request)
    {
        $request->validate([
            'rfid' => 'required|string|size:10',
        ]);
        $rfid = $request->input('rfid');
        if (! $rfid) {
            return response()->json(['status' => 'error', 'message' => 'RFID tidak boleh kosong'], 400);
        }
        if (Pegawai::where('rfid', $rfid)->exists()) {
            return response()->json(['status' => 'success', 'message' => 'Valid Pegawai']);
        }
        if (Siswa::where('rfid', $rfid)->exists()) {
            return response()->json(['status' => 'success', 'message' => 'Valid Siswa']);
        }

        return response()->json(['status' => 'error', 'message' => 'RFID tidak dikenali'], 404);
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

        $results = collect($request->data)->map(
            fn ($item) => $service->prosesPresensi($item['rfid'], $item['timestamp'], true, $item['device_id'])
                + ['rfid' => $item['rfid'], 'timestamp' => $item['timestamp']]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk sync selesai',
            'data' => $results,
        ]);
    }

    public function health()
    {
        try {
            DB::connection()->getPdo();
            $tablesExist = DB::getSchemaBuilder()->hasTable('presensi_pegawais') &&
                DB::getSchemaBuilder()->hasTable('presensi_siswas') &&
                DB::getSchemaBuilder()->hasTable('jadwal_presensis');
            if (! $tablesExist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Database tables not found',
                    'timestamp' => now()->toISOString(),
                ], 503);
            }
            $jadwalHariIni = JadwalPresensi::where('status', true)
                ->where('hari', now()->isoFormat('dddd'))
                ->exists();

            return response()->json([
                'status' => 'success',
                'message' => 'API is healthy',
                'data' => [
                    'server_time' => now()->toISOString(),
                    'server_timezone' => config('app.timezone'),
                    'database_status' => 'connected',
                    'jadwal_hari_ini' => $jadwalHariIni,
                    'api_version' => '1.0',
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
        try {
            $startTime = now()->subHours($hours);
            $presensiPegawai = PresensiPegawai::where('created_at', '>=', $startTime)->count();
            $presensiSiswa = PresensiSiswa::where('created_at', '>=', $startTime)->count();
            $syncedPegawai = PresensiPegawai::where('created_at', '>=', $startTime)
                ->where('is_synced', true)->count();
            $syncedSiswa = PresensiSiswa::where('created_at', '>=', $startTime)
                ->where('is_synced', true)->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Device statistics retrieved',
                'data' => [
                    'device_id' => $deviceId,
                    'period_hours' => $hours,
                    'start_time' => $startTime->toISOString(),
                    'end_time' => now()->toISOString(),
                    'total_presensi' => $presensiPegawai + $presensiSiswa,
                    'presensi_pegawai' => $presensiPegawai,
                    'presensi_siswa' => $presensiSiswa,
                    'synced_records' => $syncedPegawai + $syncedSiswa,
                    'sync_rate' => $presensiPegawai + $presensiSiswa > 0
                        ? round((($syncedPegawai + $syncedSiswa) / ($presensiPegawai + $presensiSiswa)) * 100, 2)
                        : 0,
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
}
