<?php

namespace App\Filament\Resources\PresensiPegawaiResource\Pages;

use App\Filament\Resources\PresensiPegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPresensiPegawai extends ViewRecord
{
    protected static string $resource = PresensiPegawaiResource::class;

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
