<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use App\Filament\Resources\InformasiResource;
use App\Jobs\BroadcastInformasi;
use App\Jobs\SendDatabaseNotification;
use App\Models\Informasi;
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
            Notification::make()
                ->title('Informasi Baru: '.$record->judul)
                ->body('Ada informasi baru yang telah dipublikasikan.')
                ->success()
                ->send();
            SendDatabaseNotification::dispatch($record);
            BroadcastInformasi::dispatch($record);
            Notification::make()
                ->title('Proses Notifikasi Dijadwalkan')
                ->body('Notifikasi database dan WhatsApp sedang dikirim di background.')
                ->info()
                ->send();
        }

        return $record;
    }
}
