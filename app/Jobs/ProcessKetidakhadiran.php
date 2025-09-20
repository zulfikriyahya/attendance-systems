<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Siswa;
use App\Models\Pegawai;
use App\Enums\StatusPulang;
use App\Enums\StatusPresensi;
use App\Models\PresensiSiswa;
use Illuminate\Bus\Queueable;
use App\Models\JadwalPresensi;
use App\Models\PresensiPegawai;
use App\Services\WhatsappDelayService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessKetidakhadiran implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WhatsappDelayService $delayService;

    public function __construct()
    {
        $this->delayService = app(WhatsappDelayService::class);
    }

    public function handle(): void
    {
        $now = now();
        $tanggal = $now->toDateString();
        $hari = $now->isoFormat('dddd');

        $jabatanIds = JadwalPresensi::query()
            ->aktif()
            ->where('hari', $hari)
            ->with('jabatans:id')
            ->get()
            ->pluck('jabatans')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->values();

        $jadwal = JadwalPresensi::where('status', true)
            ->where('hari', $hari)
            ->first();

        if (! $jadwal) {
            info('❌ Tidak ada jadwal presensi aktif hari ini.');
            return;
        }

        $this->cekPresensiMasuk($now, $tanggal, $jadwal, $jabatanIds);
        $this->cekPresensiPulang($now, $tanggal, $jadwal, $jabatanIds);

        info('✅ Proses pengecekan presensi selesai oleh job.');
    }

    protected function cekPresensiMasuk(Carbon $now, string $tanggal, JadwalPresensi $jadwal, $jabatanIds): void
    {
        $batasAlfa = Carbon::createFromFormat('H:i:s', $jadwal->jamDatang)->addHours(1);
        if ($now->lessThan($batasAlfa)) {
            return;
        }

        info('⏰ Mengecek presensi masuk (Alfa)...');

        $notifCounter = 0; // Counter untuk delay calculation

        // Process Pegawai
        Pegawai::with('jabatan.instansi', 'user')
            ->where('status', true)
            ->whereIn('jabatan_id', $jabatanIds)
            ->whereDoesntHave('presensiPegawai', fn($q) => $q->whereDate('tanggal', $tanggal))
            ->chunk(100, function ($pegawaiBatch) use ($tanggal, &$notifCounter) {
                foreach ($pegawaiBatch as $pegawai) {
                    PresensiPegawai::create([
                        'pegawai_id' => $pegawai->id,
                        'tanggal' => $tanggal,
                        'statusPresensi' => StatusPresensi::Alfa,
                    ]);

                    $nama = $pegawai->user?->name ?? $pegawai->nama;
                    $instansi = $pegawai->jabatan->instansi?->nama ?? 'MTs Negeri 1 Pandeglang';

                    // Use centralized delay service
                    $delay = $this->delayService->calculateBulkDelay($notifCounter, 'alfa');

                    // Use unified job with correct type
                    SendWhatsappMessage::dispatch(
                        $pegawai->telepon,
                        'presensi_bulk', // Use bulk type
                        [
                            'jenis' => 'Presensi Masuk',
                            'status' => StatusPresensi::Alfa->value,
                            'waktu' => '-',
                            'nama' => $nama,
                            'isSiswa' => false,
                            'instansi' => $instansi,
                        ]
                    )->delay($delay);

                    $notifCounter++;
                    info("👤 Pegawai Alfa: {$nama} (delay: {$delay->diffInMinutes(now())} menit)");
                }
            });

        // Process Siswa
        Siswa::with('jabatan.instansi', 'user')
            ->where('status', true)
            ->whereIn('jabatan_id', $jabatanIds)
            ->whereDoesntHave('presensiSiswa', fn($q) => $q->whereDate('tanggal', $tanggal))
            ->chunk(100, function ($siswaBatch) use ($tanggal, &$notifCounter) {
                foreach ($siswaBatch as $siswa) {
                    PresensiSiswa::create([
                        'siswa_id' => $siswa->id,
                        'tanggal' => $tanggal,
                        'statusPresensi' => StatusPresensi::Alfa,
                    ]);

                    $nama = $siswa->user?->name ?? $siswa->nama;
                    $instansi = $siswa->jabatan->instansi?->nama ?? 'MTs Negeri 1 Pandeglang';

                    // Use centralized delay service
                    $delay = $this->delayService->calculateBulkDelay($notifCounter, 'alfa');

                    // Use unified job with correct type
                    SendWhatsappMessage::dispatch(
                        $siswa->telepon,
                        'presensi_bulk', // Use bulk type
                        [
                            'jenis' => 'Presensi Masuk',
                            'status' => StatusPresensi::Alfa->value,
                            'waktu' => '-',
                            'nama' => $nama,
                            'isSiswa' => true,
                            'instansi' => $instansi,
                        ]
                    )->delay($delay);

                    $notifCounter++;
                    info("🎒 Siswa Alfa: {$nama} (delay: {$delay->diffInMinutes(now())} menit)");
                }
            });
    }

    protected function cekPresensiPulang(Carbon $now, string $tanggal, JadwalPresensi $jadwal, $jabatanIds): void
    {
        $batasPulang = Carbon::createFromFormat('H:i:s', $jadwal->jamPulang)->addHours(1);
        if ($now->lessThan($batasPulang)) {
            return;
        }

        info('⏰ Mengecek presensi pulang (Mangkir/Bolos)...');

        $pengecualianStatus = [
            StatusPresensi::Alfa->value,
            StatusPresensi::Dispen->value,
            StatusPresensi::Sakit->value,
            StatusPresensi::Izin->value,
            StatusPresensi::Cuti->value,
            StatusPresensi::DinasLuar->value,
            StatusPresensi::Libur->value,
        ];

        $notifCounter = 0; // Counter untuk delay calculation

        // Process Pegawai Mangkir
        PresensiPegawai::with('pegawai.user', 'pegawai.jabatan.instansi')
            ->whereDate('tanggal', $tanggal)
            ->whereNull('jamPulang')
            ->whereNull('statusPulang')
            ->whereHas('pegawai', fn($q) => $q->whereIn('jabatan_id', $jabatanIds))
            ->whereNotIn('statusPresensi', $pengecualianStatus)
            ->chunk(100, function ($presensiBatch) use (&$notifCounter) {
                foreach ($presensiBatch as $presensi) {
                    $statusPulang = StatusPulang::Mangkir->value;
                    $presensi->update(['statusPulang' => $statusPulang]);

                    $pegawai = $presensi->pegawai;
                    $nama = $pegawai->user?->name ?? $pegawai->nama;
                    $instansi = $pegawai->jabatan->instansi?->nama ?? 'MTs Negeri 1 Pandeglang';

                    // Use centralized delay service
                    $delay = $this->delayService->calculateBulkDelay($notifCounter, 'mangkir');

                    // Use unified job
                    SendWhatsappMessage::dispatch(
                        $pegawai->telepon,
                        'presensi_bulk',
                        [
                            'jenis' => 'Presensi Pulang',
                            'status' => $statusPulang,
                            'waktu' => '-',
                            'nama' => $nama,
                            'isSiswa' => false,
                            'instansi' => $instansi,
                        ]
                    )->delay($delay);

                    $notifCounter++;
                    info("👤 Pegawai {$statusPulang}: {$nama} (delay: {$delay->diffInMinutes(now())} menit)");
                }
            });

        // Process Siswa Bolos
        PresensiSiswa::with('siswa.user', 'siswa.jabatan.instansi')
            ->whereDate('tanggal', $tanggal)
            ->whereNull('jamPulang')
            ->whereNull('statusPulang')
            ->whereHas('siswa', fn($q) => $q->whereIn('jabatan_id', $jabatanIds))
            ->whereNotIn('statusPresensi', $pengecualianStatus)
            ->chunk(100, function ($presensiBatch) use (&$notifCounter) {
                foreach ($presensiBatch as $presensi) {
                    $statusPulang = StatusPulang::Bolos->value;
                    $presensi->update(['statusPulang' => $statusPulang]);

                    $siswa = $presensi->siswa;
                    $nama = $siswa->user?->name ?? $siswa->nama;
                    $instansi = $siswa->jabatan->instansi?->nama ?? 'MTs Negeri 1 Pandeglang';

                    // Use centralized delay service
                    $delay = $this->delayService->calculateBulkDelay($notifCounter, 'bolos');

                    // Use unified job
                    SendWhatsappMessage::dispatch(
                        $siswa->telepon,
                        'presensi_bulk',
                        [
                            'jenis' => 'Presensi Pulang',
                            'status' => $statusPulang,
                            'waktu' => '-',
                            'nama' => $nama,
                            'isSiswa' => true,
                            'instansi' => $instansi,
                        ]
                    )->delay($delay);

                    $notifCounter++;
                    info("🎒 Siswa {$statusPulang}: {$nama} (delay: {$delay->diffInMinutes(now())} menit)");
                }
            });
    }
}
