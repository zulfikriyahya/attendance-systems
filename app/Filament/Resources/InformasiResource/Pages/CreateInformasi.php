<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Pegawai;
use App\Models\Informasi;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\InformasiResource;
use App\Jobs\SendInformasiWhatsappNotification;

class CreateInformasi extends CreateRecord
{
    protected static string $resource = InformasiResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Informasi
    {
        $record = Informasi::create($data);

        if ($record->status === 'Publish') {
            Notification::make()
                ->title('Informasi Baru: ' . $record->judul)
                ->body('Ada informasi baru yang telah dipublikasikan.')
                ->success()
                ->send();

            Notification::make()
                ->title('Informasi Baru: ' . $record->judul)
                ->body('Silakan cek informasi terbaru yang telah dipublikasikan.')
                ->success()
                ->sendToDatabase(User::query()->where('status', true)->get());

            // 📲 Broadcast WA ke semua siswa dan pegawai
            $this->sendInformasiToWhatsapp($record);
        }

        return $record;
    }

    /**
     * Broadcast informasi ke WhatsApp untuk semua siswa dan pegawai
     */
    private function sendInformasiToWhatsapp(Informasi $informasi): void
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $currentHour = $now->format('H');

        // Cache key per jam untuk reset otomatis
        $hourlyCacheKey = "whatsapp_informasi_hourly_{$today}_{$currentHour}";
        $hourlyCount = Cache::get($hourlyCacheKey, 0);

        // Rate limit yang aman untuk broadcast informasi
        $messagesPerMinute = 25; // Lebih konservatif untuk broadcast
        $maxDelayMinutes = 60;   // Maksimal 1 jam untuk broadcast informasi

        // Ambil semua siswa dengan nomor telepon
        $siswa = Siswa::with('jabatan.instansi')
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->get();

        // Ambil semua pegawai dengan nomor telepon  
        $pegawai = Pegawai::with('jabatan.instansi', 'user')
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->get();

        $totalRecipients = $siswa->count() + $pegawai->count();

        // Jika tidak ada penerima, return
        if ($totalRecipients === 0) {
            return;
        }

        // Proses pengiriman ke siswa
        foreach ($siswa as $index => $student) {
            $this->dispatchInformasiNotification(
                $student,
                $informasi,
                true, // isSiswa
                $hourlyCount + $index,
                $messagesPerMinute,
                $maxDelayMinutes,
                $now
            );
        }

        // Proses pengiriman ke pegawai
        $siswaCount = $siswa->count();
        foreach ($pegawai as $index => $employee) {
            $this->dispatchInformasiNotification(
                $employee,
                $informasi,
                false, // isSiswa
                $hourlyCount + $siswaCount + $index,
                $messagesPerMinute,
                $maxDelayMinutes,
                $now
            );
        }

        // Update counter dengan expire otomatis di akhir jam
        $newCount = $hourlyCount + $totalRecipients;
        Cache::put($hourlyCacheKey, $newCount, now()->endOfHour());

        // Log broadcast
        logger()->info('Informasi WhatsApp broadcast dispatched', [
            'judul' => $informasi->judul,
            'total_recipients' => $totalRecipients,
            'siswa' => $siswa->count(),
            'pegawai' => $pegawai->count()
        ]);
    }

    /**
     * Dispatch notification dengan delay yang terdistribusi
     */
    private function dispatchInformasiNotification(
        $user,
        Informasi $informasi,
        bool $isSiswa,
        int $currentIndex,
        int $messagesPerMinute,
        int $maxDelayMinutes,
        Carbon $baseTime
    ): void {
        // Hitung slot berdasarkan urutan
        $minuteSlot = floor($currentIndex / $messagesPerMinute);

        // Reset jika melebihi maksimal delay
        if ($minuteSlot >= $maxDelayMinutes) {
            $minuteSlot = $minuteSlot % $maxDelayMinutes;
        }

        // Base delay random untuk distribusi natural
        $baseDelaySeconds = rand(30, 120);

        // Slot delay untuk distribusi merata
        $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot

        // Random spread tambahan
        $randomSpread = rand(0, 45);

        // Total delay
        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread;

        // Pastikan tidak melebihi maksimal (dalam detik)
        $maxDelaySeconds = $maxDelayMinutes * 60;
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

        $delay = $baseTime->copy()->addSeconds($totalDelaySeconds);

        // Ambil nama user
        $nama = $user->user?->name ?? $user->nama ?? 'Tidak dikenal';

        // Ambil nama instansi
        $instansi = $user->jabatan?->instansi?->nama ?? 'Instansi';

        // Dispatch job dengan delay
        SendInformasiWhatsappNotification::dispatch(
            $user->telepon,
            $informasi->judul,
            $informasi->isi,
            $informasi->tanggal,
            $nama,
            $isSiswa,
            $instansi,
            $informasi->lampiran
        )->delay($delay);
    }
}