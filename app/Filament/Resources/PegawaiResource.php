<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Jabatan;
use App\Models\Pegawai;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Imports\PegawaiImporter;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PegawaiResource\Pages\EditPegawai;
use App\Filament\Resources\PegawaiResource\Pages\ViewPegawai;
use App\Filament\Resources\PegawaiResource\Pages\ListPegawais;
use App\Filament\Resources\PegawaiResource\Pages\CreatePegawai;

class PegawaiResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Data Pegawai';

    protected static ?string $navigationLabel = 'Pegawai';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'pegawai';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('User')
                    ->options(function ($get) {
                        $currentUserId = $get('user_id');

                        return User::where(function ($query) use ($currentUserId) {
                            $query->where(function ($q) {
                                $q->whereDoesntHave('pegawai')
                                    ->whereDoesntHave('siswa');
                            })
                                ->orWhere('id', $currentUserId);
                        })
                            ->where('status', true)
                            ->where('username', '!=', 'administrator')
                            ->whereDoesntHave('roles', function ($q) {
                                $q->where('name', 'administrator');
                            })
                            ->pluck('name', 'id');
                    })
                    ->when(User::count() > 10, fn($field) => $field->searchable())
                    ->preload()
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                TextInput::make('rfid')
                    ->label('RFID')
                    ->unique(ignoreRecord: true)
                    ->numeric()
                    ->required()
                    ->minLength(10)
                    ->maxLength(10)
                    ->validationMessages([
                        'unique' => 'RFID sudah pernah dipakai.',
                        'required' => 'Form ini wajib diisi.',
                        'min_digits' => 'Minimal 10 digit.',
                        'max_digits' => 'Maksimal 10 digit.',
                    ]),
                TextInput::make('nip')
                    ->label('NIP')
                    ->unique(ignoreRecord: true)
                    ->numeric()
                    ->required()
                    ->minLength(16)
                    ->maxLength(18)
                    ->validationMessages([
                        'unique' => 'RFID sudah pernah dipakai.',
                        'required' => 'Form ini wajib diisi.',
                        'min_digits' => 'Minimal 16 digit.',
                        'max_digits' => 'Maksimal 18 digit.',
                    ]),
                TextInput::make('telepon')
                    ->tel()
                    ->numeric()
                    ->minLength(10)
                    ->maxLength(13)
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                        'min_digits' => 'Minimal 10 digit.',
                        'max_digits' => 'Maksimal 13 digit.',
                    ]),
                Select::make('jenisKelamin')
                    ->options([
                        'Pria' => 'Pria',
                        'Wanita' => 'Wanita',
                    ])
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                Select::make('jabatan_id')
                    ->label('Jabatan')
                    ->relationship('jabatan', 'nama')
                    ->when(Jabatan::count() > 10, fn($field) => $field->searchable())
                    ->required()
                    ->preload()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                Textarea::make('alamat')
                    ->columnSpanFull(),
                Toggle::make('status')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        $searchable = Pegawai::count() > 10;

        return $table
            ->headerActions([
                ImportAction::make('import')
                    ->label('Impor')
                    ->outlined()
                    ->color('primary')
                    ->importer(PegawaiImporter::class)
                    ->visible(fn() => Auth::user()->hasRole('super_admin')),
            ])
            ->columns([
                ImageColumn::make('user.avatar')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('user.name')
                    ->label('Nama Lengkap')
                    ->searchable($searchable),
                BadgeColumn::make('jabatan.nama')
                    ->label('Jabatan')
                    ->color('primary')
                    ->searchable($searchable),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable($searchable),
                TextColumn::make('jenisKelamin')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pria' => 'success',
                        'Wanita' => 'danger',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Pria' => 'heroicon-o-user',
                        'Wanita' => 'heroicon-o-user',
                    }),
                TextColumn::make('telepon')
                    ->label('Telepon'),
                TextColumn::make('rfid')
                    ->label('RFID')
                    ->copyable()
                    ->searchable($searchable),
                IconColumn::make('status')
                    ->label('Status')
                    ->boolean(),
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
                    // Action::make('print')
                    //     ->label('Cetak Presensi')
                    //     ->icon('heroicon-o-printer')
                    //     ->requiresConfirmation()
                    //     ->form([
                    //         Select::make('bulan')
                    //             ->label('Bulan')
                    //             ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [
                    //                 str_pad($m, 2, '0', STR_PAD_LEFT) => Carbon::create()->month($m)->translatedFormat('F'),
                    //             ])->toArray())
                    //             ->required(),
                    //         TextInput::make('tahun')
                    //             ->label('Tahun')
                    //             ->default(now()->year)
                    //             ->numeric()
                    //             ->required(),
                    //     ])
                    //     ->action(function ($record, array $data) {
                    //         $url = route('laporan.single.pegawai', [
                    //             'pegawai' => $record->id,
                    //             'bulan' => $data['bulan'],
                    //             'tahun' => $data['tahun'],
                    //         ]);

                    //         return redirect($url);
                    //     })
                    //     ->color('gray'),
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

    public static function getRelations(): array
    {
        return [
            // Relasi Resource Presensi Pegawai
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPegawais::route('/'),
            'create' => CreatePegawai::route('/create'),
            'view' => ViewPegawai::route('/{record}'),
            'edit' => EditPegawai::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        if (! Auth::user()->hasRole(['super_admin', 'administrator'])) {
            $pegawaiId = Auth::user()->pegawai?->id;

            // Kalau user punya pegawai, filter berdasarkan id pegawai
            if ($pegawaiId) {
                $query->where('id', $pegawaiId);
            } else {
                // kalau user tidak punya pegawai, kembalikan query kosong
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }
}
