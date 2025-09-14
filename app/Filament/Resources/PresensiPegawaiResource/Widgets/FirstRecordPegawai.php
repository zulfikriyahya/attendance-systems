<?php

namespace App\Filament\Resources\PresensiPegawaiResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\PresensiPegawai;
use Filament\Widgets\TableWidget as BaseWidget;

class FirstRecordPegawai extends BaseWidget
{
    protected static ?string $heading = '5 Presensi Pegawai Pertama';
    // protected int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                PresensiPegawai::query()
                    ->with('pegawai')
                    ->latest('tanggal') 
                    ->whereNotIn('statusPresensi', ['Izin', 'Alfa', 'Sakit', 'Dinas Luar', 'Libur'])
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('pegawai.user.name')
                    ->label('Nama Pegawai'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('l, d F Y'),

                Tables\Columns\TextColumn::make('jamDatang')
                    ->label('Jam Datang')
                    ->time('H:i:s')
                    ->placeholder('-'),

                Tables\Columns\BadgeColumn::make('statusPresensi')
                    ->label('Status Presensi')
                    ->colors([
                        'success' => 'Hadir',
                        'warning' => 'Terlambat',
                        'danger' => 'Alfa',
                        'secondary' => 'Izin',
                        'info' => 'Sakit',
                        'primary' => 'Libur',
                        'warning' => 'Dispen',
                    ]),
            ])
            ->defaultSort('jamDatang', 'asc')
            ->paginated(false); // Disable pagination since we only want 5 records
    }
}
