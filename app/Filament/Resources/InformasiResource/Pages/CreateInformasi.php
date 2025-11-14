<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use App\Filament\Resources\InformasiResource;
use App\Jobs\BroadcastInformasi;
use App\Jobs\SendDatabaseNotification;
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
            // 🔔 Notifikasi langsung ke user yang sedang login
            Notification::make()
                ->title('Informasi Baru: '.$record->judul)
                ->body('Ada informasi baru yang telah dipublikasikan.')
                ->success()
                ->send();

            // 📨 Dispatch job untuk notifikasi database (background)
            SendDatabaseNotification::dispatch($record);

            // 📲 Dispatch job untuk broadcast WhatsApp (background)
            BroadcastInformasi::dispatch($record);

            // 💡 Feedback ke user bahwa proses sedang berjalan
            Notification::make()
                ->title('Proses Notifikasi Dijadwalkan')
                ->body('Notifikasi database dan WhatsApp sedang dikirim di background.')
                ->info()
                ->send();
        }

        return $record;
    }
}
