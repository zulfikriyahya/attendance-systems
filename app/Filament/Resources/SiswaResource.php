<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Jabatan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TahunPelajaran;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Filament\Imports\SiswaImporter;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Models\KelasSiswaTahunPelajaran;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Filters\SelectFilter;
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
use App\Filament\Resources\SiswaResource\Pages\EditSiswa;
use App\Filament\Resources\SiswaResource\Pages\ViewSiswa;
use App\Filament\Resources\SiswaResource\Pages\ListSiswas;
use App\Filament\Resources\SiswaResource\Pages\CreateSiswa;

class SiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static bool $shouldRegisterNavigation = true; // true

    protected static ?string $navigationGroup = 'Data Siswa';

    protected static ?string $navigationLabel = 'Siswa';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'siswa';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

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
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ])
                    ->preload(),
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
                TextInput::make('nisn')
                    ->label('NISN')
                    ->unique(ignoreRecord: true)
                    ->numeric()
                    ->required()
                    ->minLength(10)
                    ->maxLength(10)
                    ->validationMessages([
                        'unique' => 'NISN sudah pernah dipakai.',
                        'required' => 'Form ini wajib diisi.',
                        'min_digits' => 'Minimal 10 digit.',
                        'max_digits' => 'Maksimal 10 digit.',
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
                        'Pria' => 'Laki-laki',
                        'Wanita' => 'Perempuan',
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
        $searchable = Siswa::count() > 10;

        return $table
            ->headerActions([
                ImportAction::make('import')
                    ->label('Impor')
                    ->outlined()
                    ->color('primary')
                    ->importer(SiswaImporter::class)
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
                TextColumn::make('nisn')
                    ->label('NISN')
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
                TextColumn::make('kelasSaatIni')
                    ->label('Kelas')
                    ->formatStateUsing(
                        fn($state, $record) => $record->kelasSaatIni->pluck('nama')->implode(', ')
                    ),
                IconColumn::make('status')
                    ->label('Status')
                    ->boolean(),
            ])
            ->paginationPageOptions([5, 10, 25])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->options(fn() => \App\Models\Kelas::whereHas('siswaSaatIni')
                        ->orderBy('nama')
                        ->pluck('nama', 'id'))
                    ->query(function ($query, array $data): \Illuminate\Database\Eloquent\Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('kelasSaatIni', function ($query) use ($data) {
                            $query->where('kelas.id', $data['value']);
                        });
                    }),
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
                    //         $url = route('laporan.single.siswa', [
                    //             'siswa' => $record->id,
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
                    BulkAction::make('assignKelas')
                        ->label('Assign Kelas + TP')
                        ->icon('heroicon-o-building-storefront')
                        ->form([
                            Select::make('kelas_id')
                                ->label('Kelas')
                                ->options(Kelas::all()->pluck('nama', 'id'))
                                ->required(),

                            Select::make('tahun_pelajaran_id')
                                ->label('Tahun Pelajaran')
                                ->options(TahunPelajaran::where('status', true)->pluck('nama', 'id'))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $siswa) {
                                KelasSiswaTahunPelajaran::updateOrCreate([
                                    'kelas_id' => $data['kelas_id'],
                                    'siswa_id' => $siswa->id,
                                    'tahun_pelajaran_id' => $data['tahun_pelajaran_id'],
                                ]);
                            }

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Berhasil menetapkan kelas dan tahun pelajaran.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn() => Auth::user()->hasRole('super_admin')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiswas::route('/'),
            'create' => CreateSiswa::route('/create'),
            'view' => ViewSiswa::route('/{record}'),
            'edit' => EditSiswa::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        if (! Auth::user()->hasRole(['super_admin', 'administrator'])) {
            $siswaId = Auth::user()->siswa?->id;

            // Kalau user punya siswa, filter berdasarkan id siswa
            if ($siswaId) {
                $query->where('id', $siswaId);
            } else {
                // kalau user tidak punya siswa, kembalikan query kosong
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }
}
