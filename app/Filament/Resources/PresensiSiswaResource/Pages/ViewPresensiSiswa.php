<?php

namespace App\Filament\Resources\PresensiSiswaResource\Pages;

use App\Filament\Resources\PresensiSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPresensiSiswa extends ViewRecord
{
    protected static string $resource = PresensiSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        if (request()->filled('notification_id')) {
            Auth::user()
                ->unreadNotifications()
                ->where('id', request('notification_id'))
                ->first()?->markAsRead();
        }
    }
}
