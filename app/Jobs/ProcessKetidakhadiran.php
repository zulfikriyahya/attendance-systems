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
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessKetidakhadiran implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

                    // Hitung delay untuk distribusi merata
                    $delay = $this->calculateBulkDelay($notifCounter, 'alfa');

                    SendWhatsappNotificationBulk::dispatch(
                        $pegawai->telepon,
                        'Presensi Masuk',
                        StatusPresensi::Alfa->value,
                        '-',
                        $nama,
                        false,
                        $instansi
                    )->delay($delay);

                    $notifCounter++;
                    info("👤 Pegawai Alfa: {$nama} (delay: {$delay->diffInMinutes(now())} menit)");
                }
            });

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

                    // Hitung delay untuk distribusi merata
                    $delay = $this->calculateBulkDelay($notifCounter, 'alfa');

                    SendWhatsappNotificationBulk::dispatch(
                        $siswa->telepon,
                        'Presensi Masuk',
                        StatusPresensi::Alfa->value,
                        '-',
                        $nama,
                        true,
                        $instansi
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

                    // Hitung delay untuk distribusi merata
                    $delay = $this->calculateBulkDelay($notifCounter, 'mangkir');

                    SendWhatsappNotificationBulk::dispatch(
                        $pegawai->telepon,
                        'Presensi Pulang',
                        $statusPulang,
                        '-',
                        $nama,
                        false,
                        $instansi
                    )->delay($delay);

                    $notifCounter++;
                    info("👤 Pegawai {$statusPulang}: {$nama} (delay: {$delay->diffInMinutes(now())} menit)");
                }
            });

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

                    // Hitung delay untuk distribusi merata
                    $delay = $this->calculateBulkDelay($notifCounter, 'bolos');

                    SendWhatsappNotificationBulk::dispatch(
                        $siswa->telepon,
                        'Presensi Pulang',
                        $statusPulang,
                        '-',
                        $nama,
                        true,
                        $instansi
                    )->delay($delay);

                    $notifCounter++;
                    info("🎒 Siswa {$statusPulang}: {$nama} (delay: {$delay->diffInMinutes(now())} menit)");
                }
            });
    }

    /**
     * Hitung delay untuk bulk notification agar tidak burst
     */
    private function calculateBulkDelay(int $counter, string $type): Carbon
    {
        $now = now();

        // Rate yang aman untuk bulk notification (lebih konservatif)
        $messagesPerMinute = 20; // Lebih pelan karena ini bulk/mass notification

        // Hitung delay berdasarkan counter
        $minuteSlot = floor($counter / $messagesPerMinute);

        // Base delay + slot delay
        $baseDelaySeconds = rand(10, 30); // Delay dasar
        $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
        $randomSpread = rand(0, 60); // Random spread lebih besar

        // Priority untuk different types
        switch ($type) {
            case 'alfa':
                // Alfa notification: delay normal
                $priorityOffset = 0;
                break;
            case 'mangkir':
            case 'bolos':
                // Mangkir/Bolos: delay sedikit lebih lama (bukan urgent)
                $priorityOffset = rand(60, 180); // 1-3 menit extra
                break;
            default:
                $priorityOffset = 0;
        }

        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $priorityOffset;

        // Maksimal delay 2 jam untuk bulk notification
        $maxDelaySeconds = 2 * 60 * 60; // 2 jam
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

        return $now->addSeconds($totalDelaySeconds);
    }

}
