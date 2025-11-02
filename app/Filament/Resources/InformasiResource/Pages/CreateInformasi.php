<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use App\Filament\Resources\InformasiResource;
use App\Jobs\BroadcastInformasi;
use App\Models\Informasi;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

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
            // 🔔 Notifikasi Filament ke user login
            Notification::make()
                ->title('Informasi Baru: ' . $record->judul)
                ->body('Ada informasi baru yang telah dipublikasikan.')
                ->success()
                ->send();

            // TODO: Kirim ke worker
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

            // 📲 Dispatch job untuk broadcast WhatsApp
            BroadcastInformasi::dispatch($record);

            // Optional: Tambahkan notifikasi bahwa broadcast sedang diproses
            Notification::make()
                ->title('Broadcast WhatsApp Dijadwalkan')
                ->body('Pesan WhatsApp akan segera dikirim ke penerima.')
                ->info()
                ->send();
        }

        return $record;
    }
}
