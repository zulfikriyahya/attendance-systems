<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JadwalPresensiResource\Pages\CreateJadwalPresensi;
use App\Filament\Resources\JadwalPresensiResource\Pages\EditJadwalPresensi;
use App\Filament\Resources\JadwalPresensiResource\Pages\ListJadwalPresensis;
use App\Filament\Resources\JadwalPresensiResource\Pages\ViewJadwalPresensi;
use App\Models\JadwalPresensi;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class JadwalPresensiResource extends Resource
{
    protected static ?string $model = JadwalPresensi::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Jadwal Presensi';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'jadwal-presensi';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Jadwal Presensi')
                    ->collapsible()
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Select::make('instansi_id')
                            ->label('Nama Instansi')
                            ->relationship('instansi', 'nama')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TextInput::make('nama')
                            ->label('Nama Jadwal')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        Select::make('hari')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                                'Minggu' => 'Minggu',
                            ])
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        DateTimePicker::make('jamDatang')
                            ->label('Jam Datang')
                            ->displayFormat('H:i:s')
                            ->format('H:i:s')
                            ->withoutDate()
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        DateTimePicker::make('jamPulang')
                            ->label('Jam Pulang')
                            ->displayFormat('H:i:s')
                            ->format('H:i:s')
                            ->withoutDate()
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        Select::make('status')
                            ->default(true)
                            ->native(false)
                            ->options([
                                true => 'Aktif',
                                false => 'Non Aktif',
                            ])
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        Textarea::make('deskripsi')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([])
            ->columns([
                ImageColumn::make('instansi.logoInstansi')
                    ->disabledClick()
                    ->label('Logo Instansi')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('instansi.nama')
                    ->disabledClick()
                    ->label('Nama Instansi'),
                TextColumn::make('nama')
                    ->disabledClick()
                    ->label('Nama Jadwal')
                    ->sortable(),
                TextColumn::make('hari')
                    ->disabledClick()
                    ->sortable(),
                TextColumn::make('jamDatang')
                    ->disabledClick()
                    ->label('Jam Datang')
                    ->suffix(' WIB'),
                TextColumn::make('jamPulang')
                    ->disabledClick()
                    ->label('Jam Pulang')
                    ->suffix(' WIB'),
                ToggleColumn::make('status')
                    ->disabledClick()
                    ->label('Status')
                    ->disabled(! Auth::user()->hasRole('super_admin'))
                    ->tooltip(fn ($state) => $state !== true ? 'Aktifkan!' : 'Nonaktifkan!'),
            ])
            ->paginationPageOptions([5, 10, 25])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View')
                        ->color(Color::Zinc)
                        ->size('sm')
                        ->icon('heroicon-o-eye'),
                    EditAction::make()
                        ->label('Edit')
                        ->color(Color::Green)
                        ->size('sm')
                        ->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()
                        ->label('Delete')
                        ->color(Color::Red)
                        ->size('sm')
                        ->icon('heroicon-o-minus-circle'),
                    ForceDeleteAction::make()
                        ->label('Force Delete')
                        ->color(Color::Red)
                        ->size('sm')
                        ->icon('heroicon-o-trash'),
                    RestoreAction::make()
                        ->label('Restore')
                        ->color(Color::Blue)
                        ->size('sm')
                        ->icon('heroicon-o-arrow-path'),
                ]),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Delete')
                    ->button()
                    ->outlined()
                    ->size('xs')
                    ->icon('heroicon-o-minus-circle')
                    ->color(Color::Red)
                    ->visible(fn () => Auth::user()->hasRole(['super_admin'])),
                ForceDeleteBulkAction::make()
                    ->label('Force Delete')
                    ->button()
                    ->outlined()
                    ->size('xs')
                    ->icon('heroicon-o-trash')
                    ->color(Color::Red)
                    ->visible(fn () => Auth::user()->hasRole(['super_admin'])),
                RestoreBulkAction::make()
                    ->label('Restore')
                    ->button()
                    ->outlined()
                    ->size('xs')
                    ->icon('heroicon-o-arrow-path')
                    ->color(Color::Blue)
                    ->visible(fn () => Auth::user()->hasRole(['super_admin'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwalPresensis::route('/'),
            'create' => CreateJadwalPresensi::route('/create'),
            'view' => ViewJadwalPresensi::route('/{record}'),
            'edit' => EditJadwalPresensi::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
