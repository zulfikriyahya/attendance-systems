<?php

namespace App\Filament\Resources\TahunPelajaranResource\Pages;

use App\Filament\Resources\TahunPelajaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ListTahunPelajarans extends ListRecords
{
    protected static string $resource = TahunPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        if (Auth::user()->hasRole('super_admin')) {
            return [
                CreateAction::make()
                    ->label('Tambah Tahun')
                    ->outlined()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Emerald),
            ];
        }

        return [];
    }
}
