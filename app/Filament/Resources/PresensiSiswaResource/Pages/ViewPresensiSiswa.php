<?php

namespace App\Filament\Resources\PresensiSiswaResource\Pages;

use App\Filament\Resources\PresensiSiswaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ViewPresensiSiswa extends ViewRecord
{
    protected static string $resource = PresensiSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->color(Color::Green)
                ->size('sm')
                ->icon('heroicon-o-pencil-square')
                ->outlined(),
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
