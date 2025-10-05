<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\JadwalPresensi;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\JadwalPresensiResource\Pages\EditJadwalPresensi;
use App\Filament\Resources\JadwalPresensiResource\Pages\ViewJadwalPresensi;
use App\Filament\Resources\JadwalPresensiResource\Pages\ListJadwalPresensis;
use App\Filament\Resources\JadwalPresensiResource\Pages\CreateJadwalPresensi;

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
                Textarea::make('deskripsi')
                    ->columnSpanFull(),
                Toggle::make('status')
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([])
            ->columns([
                ImageColumn::make('instansi.logoInstansi')
                    ->label('Logo Instansi')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('instansi.nama')
                    ->label('Nama Instansi'),
                TextColumn::make('nama')
                    ->label('Nama Jadwal')
                    ->sortable(),
                TextColumn::make('hari')
                    ->sortable(),
                TextColumn::make('jamDatang')
                    ->label('Jam Datang')
                    ->suffix(' WIB'),
                TextColumn::make('jamPulang')
                    ->label('Jam Pulang')
                    ->suffix(' WIB'),
                ToggleColumn::make('status')
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
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
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
