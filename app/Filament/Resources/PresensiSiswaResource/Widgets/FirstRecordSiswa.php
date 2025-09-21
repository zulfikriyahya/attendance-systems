<?php

namespace App\Filament\Resources\PresensiSiswaResource\Widgets;

use App\Models\PresensiSiswa;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FirstRecordSiswa extends BaseWidget
{
    protected static ?string $heading = '5 Presensi Siswa Pertama';

    // protected int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {

        return $table
            ->query(
                PresensiSiswa::query()
                    ->with('siswa')
                    ->latest('tanggal')
                    ->whereNotIn('statusPresensi', ['Izin', 'Alfa', 'Sakit', 'Dispen', 'Libur'])
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('siswa.user.name')
                    ->label('Nama Siswa'),

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
