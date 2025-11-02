<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use App\Filament\Resources\InformasiResource;
use App\Jobs\SendWhatsappMessage;
use App\Models\Informasi;
use App\Models\Pegawai;
use App\Models\Siswa;
use App\Models\User;
use App\Services\WhatsappDelayService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInformasi extends CreateRecord
{
    protected static string $resource = InformasiResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    // TODO: Kirim ke Jobs
    // FIXME: filter hanya jabatan aktif
    protected function handleRecordCreation(array $data): Informasi
    {
        $record = Informasi::create($data);

        if ($record->status === 'Publish') {
            // 🔔 Notifikasi Filament ke user login
            Notification::make()
                ->title('Informasi Baru: ' . $record->judul)
                ->body('Ada informasi baru yang telah dipublikasikan.')
                ->success()
                ->send();

            // 🔔 Notifikasi DB ke semua user aktif
            Notification::make()
                ->title('Informasi Baru: ' . $record->judul)
                ->body('Silakan cek informasi terbaru yang telah dipublikasikan.')
                ->success()
                ->sendToDatabase(
                    User::query()
                        ->where('status', true)
                        ->where(function ($query) use ($record) {
                            $query->whereHas('pegawai', fn($q) => $q->where('jabatan_id', $record->jabatan_id))
                                ->orWhereHas('siswa', fn($q) => $q->where('jabatan_id', $record->jabatan_id));
                        })
                        ->get()
                );

            // 📲 Broadcast WA ke semua siswa dan pegawai
            $this->sendInformasiToWhatsapp($record);
        }

        return $record;
    }

    // TODO: Kirim ke Jobs
    /**
     * Broadcast informasi ke WhatsApp menggunakan unified job system
     */
    private function sendInformasiToWhatsapp(Informasi $informasi): void
    {
        $delayService = app(WhatsappDelayService::class);
        $notifCounter = 0;

        // Ambil semua siswa dengan nomor telepon
        $siswa = Siswa::with('jabatan.instansi')
            ->where('jabatan_id', $informasi->jabatan_id)
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->where('status', true)
            ->get();

        // Ambil semua pegawai dengan nomor telepon
        $pegawai = Pegawai::with('jabatan.instansi', 'user')
            ->where('jabatan_id', $informasi->jabatan_id)
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->where('status', true)
            ->get();

        $totalRecipients = $siswa->count() + $pegawai->count();

        // Jika tidak ada penerima, return
        if ($totalRecipients === 0) {
            logger()->warning('No recipients found for informasi broadcast', [
                'informasi_id' => $informasi->id,
                'judul' => $informasi->judul,
            ]);

            return;
        }

        // Proses pengiriman ke siswa
        foreach ($siswa as $student) {
            $nama = $student->user?->name ?? $student->nama ?? 'Siswa';
            $instansi = $student->jabatan?->instansi?->nama ?? 'Instansi';

            $delay = $delayService->calculateBulkDelay($notifCounter, 'informasi');

            SendWhatsappMessage::dispatch(
                $student->telepon,
                'informasi',
                [
                    'judul' => $informasi->judul,
                    'isi' => $informasi->isi,
                    'nama' => $nama,
                    'instansi' => $instansi,
                    'lampiran' => $informasi->lampiran,
                    'isSiswa' => true,
                ]
            )->delay($delay);

            $notifCounter++;
        }

        // Proses pengiriman ke pegawai
        foreach ($pegawai as $employee) {
            $nama = $employee->user?->name ?? $employee->nama ?? 'Pegawai';
            $instansi = $employee->jabatan?->instansi?->nama ?? 'Instansi';

            $delay = $delayService->calculateBulkDelay($notifCounter, 'informasi');

            SendWhatsappMessage::dispatch(
                $employee->telepon,
                'informasi',
                [
                    'judul' => $informasi->judul,
                    'isi' => $informasi->isi,
                    'nama' => $nama,
                    'instansi' => $instansi,
                    'lampiran' => $informasi->lampiran,
                    'isSiswa' => false,
                ]
            )->delay($delay);

            $notifCounter++;
        }

        // Log broadcast
        logger()->info('Informasi WhatsApp broadcast dispatched', [
            'informasi_id' => $informasi->id,
            'judul' => $informasi->judul,
            'total_recipients' => $totalRecipients,
            'siswa' => $siswa->count(),
            'pegawai' => $pegawai->count(),
            'max_delay_minutes' => $delayService->calculateBulkDelay($notifCounter - 1, 'informasi')->diffInMinutes(now()),
        ]);
    }
}
