<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanKartuResource\Pages\CreatePengajuanKartu;
use App\Filament\Resources\PengajuanKartuResource\Pages\EditPengajuanKartu;
use App\Filament\Resources\PengajuanKartuResource\Pages\ListPengajuanKartus;
use App\Filament\Resources\PengajuanKartuResource\Pages\ViewPengajuanKartu;
use App\Jobs\SendPengajuanKartuNotification;
use App\Models\PengajuanKartu;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PengajuanKartuResource extends Resource
{
    protected static ?string $model = PengajuanKartu::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Kartu Presensi';

    protected static ?string $navigationLabel = 'Pengajuan Kartu';

    protected static ?string $recordTitleAttribute = 'nomorPengajuanKartu';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'pengajuan-kartu';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('super_admin')) {
            return 'Fitur Baru';
        }

        return static::getModel()::where('status', 'Pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('super_admin')) {
            return 'success';
        }

        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 12,
                ])
                    ->schema([
                        Section::make('Pengajuan Kartu')
                            ->collapsible()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 8,
                            ])
                            ->columns(3)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Nama Pengguna')
                                    ->options(function ($get) {
                                        $currentUserId = $get('user_id');

                                        return User::where('status', true)
                                            ->where('username', '!=', 'administrator')
                                            ->whereDoesntHave('roles', function ($q) {
                                                $q->whereIn('name', ['administrator']);
                                            })
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabledOn('edit')
                                    ->hidden(fn () => ! Auth::user()->hasRole(['super_admin', 'wali_kelas']))
                                    ->default(fn () => Auth::user()->hasRole(['super_admin', 'wali_kelas']) ? null : Auth::id())
                                    ->rules([
                                        'required',
                                        function () {
                                            return function (string $attribute, $value, \Closure $fail) {
                                                if ($value) {
                                                    $existingPengajuan = PengajuanKartu::where('user_id', $value)
                                                        ->whereIn('status', ['Pending', 'Proses'])
                                                        ->exists();

                                                    if ($existingPengajuan) {
                                                        $fail('Pengguna ini masih memiliki pengajuan kartu yang sedang diproses.');
                                                    }
                                                }
                                            };
                                        },
                                    ]),

                                Select::make('alasanPengajuanKartu')
                                    ->label('Alasan Pengajuan')
                                    ->required()
                                    ->native(false)
                                    ->options([
                                        'Baru' => 'Pembuatan Baru',
                                        'Rusak' => 'Kartu Rusak',
                                        'Hilang' => 'Kartu Hilang',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state === 'Baru') {
                                            $set('biaya', 0);
                                        } else {
                                            $set('biaya', config('app.biaya_kartu'));
                                        }
                                    }),

                                DateTimePicker::make('tanggalPengajuanKartu')
                                    ->label('Tanggal Pengajuan')
                                    ->required()
                                    ->displayFormat('l, d F Y H:i')
                                    ->native(false)
                                    ->default(now())
                                    ->maxDate(now()),
                            ]),

                        Section::make('Detail')
                            ->collapsible()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 4,
                            ])
                            ->columns(2)
                            ->schema([
                                TextInput::make('biaya')
                                    ->label('Biaya Pembuatan Kartu')
                                    ->prefix('Rp.')
                                    ->integer()
                                    ->default(function (callable $get) {
                                        return $get('alasanPengajuanKartu') === 'Baru'
                                            ? 0
                                            : config('app.biaya_kartu');
                                    })
                                    ->maxValue(50000)
                                    ->maxLength(5)
                                    ->minLength(1)
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Form ini wajib diisi',
                                    ])
                                    ->disabled(fn () => ! Auth::user()->hasRole(['super_admin', 'wali_kelas'])),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'Pending' => 'Pending',
                                        'Proses' => 'Proses',
                                        'Selesai' => 'Selesai',
                                    ])
                                    ->native(false)
                                    ->default('Pending')
                                    ->required()
                                    ->disabled(fn () => ! Auth::user()->hasRole(['super_admin', 'wali_kelas'])),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('ajukan-kartu-baru')
                    ->label('Pengajuan Kartu Baru')
                    ->icon('heroicon-o-identification')
                    ->color(Color::Violet)
                    ->outlined()
                    ->requiresConfirmation()
                    ->hidden(Auth::user()->hasRole('super_admin'))
                    ->visible(function () {
                        // Hanya tampil jika user belum punya pengajuan yang pending/proses
                        $userId = Auth::id();
                        $existingPengajuan = PengajuanKartu::where('user_id', $userId)
                            ->whereIn('status', ['Pending', 'Proses'])
                            ->exists();

                        return ! $existingPengajuan;
                    })
                    ->form([
                        DateTimePicker::make('tanggalPengajuanKartu')
                            ->label('Tanggal Pengajuan')
                            ->required()
                            ->displayFormat('l, d F Y H:i')
                            ->native(false)
                            ->validationMessages(['required' => 'Form ini wajib diisi.'])
                            ->default(now())
                            ->maxDate(now()),

                        Textarea::make('alasanPengajuanKartu')
                            ->label('Alasan Pengajuan')
                            ->required()
                            ->validationMessages(['required' => 'Form ini wajib diisi.'])
                            ->placeholder('Contoh: Kartu hilang, kartu rusak, dll.')
                            ->rows(3),

                        TextInput::make('biaya')
                            ->label('Biaya Pembuatan Kartu')
                            ->prefix('Rp.')
                            ->integer()
                            ->maxValue(50000)
                            ->maxLength(5)
                            ->minLength(1)
                            ->default(config('app.biaya_kartu'))
                            ->dehydrated()
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi',
                            ])
                            ->readOnly(! Auth::user()->hasRole('super_admin')),
                    ])
                    ->action(function (array $data) {
                        $userId = Auth::id();

                        if (! $userId) {
                            Notification::make()
                                ->title('Error')
                                ->body('Data user tidak ditemukan.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Double check untuk memastikan tidak ada pengajuan yang sedang berjalan
                        $existingPengajuan = PengajuanKartu::where('user_id', $userId)
                            ->whereIn('status', ['Pending', 'Proses'])
                            ->first();

                        if ($existingPengajuan) {
                            Notification::make()
                                ->title('Pengajuan Gagal')
                                ->body('Anda masih memiliki pengajuan kartu yang sedang diproses.')
                                ->warning()
                                ->send();

                            return;
                        }

                        // Generate nomor pengajuan yang unik
                        $nomorPengajuan = self::generateNomorPengajuan($userId);

                        $pengajuanKartu = PengajuanKartu::create([
                            'user_id' => $userId,
                            'tanggalPengajuanKartu' => Carbon::parse($data['tanggalPengajuanKartu']),
                            'alasanPengajuanKartu' => $data['alasanPengajuanKartu'],
                            'nomorPengajuanKartu' => $nomorPengajuan,
                            'biaya' => $data['biaya'],
                            'status' => 'Pending',
                        ]);

                        Notification::make()
                            ->title('Pengajuan Berhasil')
                            ->body('Pengajuan kartu baru Anda telah berhasil disubmit dengan nomor: '.$pengajuanKartu->nomorPengajuanKartu)
                            ->success()
                            ->duration(5000)
                            ->send();

                        // Kirim notifikasi ke admin
                        self::sendNotificationToAdmins($pengajuanKartu, $data['alasanPengajuanKartu']);
                    }),
            ])
            ->columns([
                TextColumn::make('nomorPengajuanKartu')
                    ->disabledClick()
                    ->label('Nomor Pengajuan')
                    ->searchable(PengajuanKartu::all()->count() > 10)
                    ->sortable()
                    ->badge()
                    ->copyable(),

                TextColumn::make('user.name')
                    ->disabledClick()
                    ->label('Nama User')
                    ->searchable(PengajuanKartu::all()->count() > 10)
                    ->sortable(),

                TextColumn::make('tanggalPengajuanKartu')
                    ->disabledClick()
                    ->label('Tanggal Pengajuan')
                    ->date('l, d F Y')
                    ->sortable(),

                TextColumn::make('alasanPengajuanKartu')
                    ->disabledClick()
                    ->label('Alasan Pengajuan')
                    ->badge()
                    ->colors([
                        'info' => 'Baru',
                        'warning' => 'Rusak',
                        'danger' => 'Hilang',
                    ])
                    ->sortable(),

                TextColumn::make('status')
                    ->disabledClick()
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'Pending',
                        'info' => 'Proses',
                        'success' => 'Selesai',
                    ])
                    ->sortable(),
                ToggleColumn::make('statusAmbil')
                    ->disabledClick()
                    ->label('Penyerahan')
                    ->disabled(fn ($record) => $record->status !== 'Selesai' && Auth::user()->hasRole('super_admin'))
                    ->tooltip(fn ($record) => $record->status !== 'Selesai' ? 'Sudah selesai, tidak bisa diubah' : null)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Proses' => 'Proses',
                        'Selesai' => 'Selesai',
                    ])
                    ->multiple()
                    ->visible(fn () => Auth::user()->hasRole('super_admin')),

                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Auth::user()->hasRole('super_admin')),

                TrashedFilter::make()
                    ->visible(fn () => Auth::user()->hasRole('super_admin')),
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
                        ->icon('heroicon-o-pencil-square')
                        ->hidden(fn ($record) => $record->statusAmbil === true && Auth::user()->hasRole('super_admin'))
                        ->visible(fn () => Auth::user()->hasRole('super_admin')),
                    DeleteAction::make()
                        ->label('Delete')
                        ->color(Color::Red)
                        ->size('sm')
                        ->icon('heroicon-o-minus-circle')
                        ->visible(fn () => Auth::user()->hasRole('super_admin')),
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

                Action::make('approve')
                    ->label('Setujui')
                    ->button()
                    ->outlined()
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn (PengajuanKartu $record) => Auth::user()->hasRole('super_admin') &&
                            $record->status === 'Pending'
                    )
                    ->action(function (PengajuanKartu $record) {
                        $record->update(['status' => 'Proses']);

                        // Notifikasi ke admin
                        Notification::make()
                            ->title('Status Diperbarui')
                            ->body('Pengajuan telah disetujui dan status diubah ke Proses.')
                            ->success()
                            ->send();

                        // Notifikasi database ke user
                        Notification::make()
                            ->title('Pengajuan kartu Anda sedang diproses.')
                            ->body('Pengajuan kartu Anda dengan nomor '.$record->nomorPengajuanKartu.' sedang diproses.')
                            ->success()
                            ->sendToDatabase($record->user);

                        // Kirim WhatsApp via Job dengan tipe 'proses'
                        SendPengajuanKartuNotification::dispatch($record, 'proses')
                            ->onQueue('whatsapp');

                        logger()->info('Pengajuan kartu approved', [
                            'pengajuan_id' => $record->id,
                            'nomor_pengajuan' => $record->nomorPengajuanKartu,
                            'user_id' => $record->user->id,
                        ]);
                    }),

                Action::make('complete')
                    ->label('Selesaikan')
                    ->button()
                    ->outlined()
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn (PengajuanKartu $record) => Auth::user()->hasRole('super_admin') &&
                            $record->status === 'Proses'
                    )
                    ->action(function (PengajuanKartu $record) {
                        $record->update(['status' => 'Selesai']);

                        // Notifikasi ke admin
                        Notification::make()
                            ->title('Pengajuan Selesai')
                            ->body('Pengajuan kartu telah diselesaikan.')
                            ->success()
                            ->send();

                        // Notifikasi database ke user
                        $biayaFormatted = number_format($record->biaya, 0, ',', '.');
                        Notification::make()
                            ->title('Kartu Siap Diambil di Ruang PTSP')
                            ->body('Pengajuan kartu Anda dengan nomor '.$record->nomorPengajuanKartu.' telah selesai diproses. (Biaya pembuatan kartu Rp. '.$biayaFormatted.')')
                            ->success()
                            ->sendToDatabase($record->user);

                        // Kirim WhatsApp via Job dengan tipe 'selesai'
                        SendPengajuanKartuNotification::dispatch($record, 'selesai')
                            ->onQueue('whatsapp');

                        logger()->info('Pengajuan kartu completed', [
                            'pengajuan_id' => $record->id,
                            'nomor_pengajuan' => $record->nomorPengajuanKartu,
                            'user_id' => $record->user->id,
                            'biaya' => $record->biaya,
                        ]);
                    }),

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
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPengajuanKartus::route('/'),
            'create' => CreatePengajuanKartu::route('/create'),
            'view' => ViewPengajuanKartu::route('/{record}'),
            'edit' => EditPengajuanKartu::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // Filter berdasarkan role
        if (! Auth::user()->hasRole(['super_admin', 'wali_kelas'])) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    private static function generateNomorPengajuan(int $userId): string
    {
        $today = now()->format('Ymd');
        $userIdPadded = str_pad($userId, 4, '0', STR_PAD_LEFT);

        // Cari nomor urut terakhir untuk hari ini
        $lastNumber = PengajuanKartu::where('nomorPengajuanKartu', 'LIKE', "PK-{$today}-%")
            ->orderBy('nomorPengajuanKartu', 'desc')
            ->first();

        $sequence = 1;
        if ($lastNumber) {
            $parts = explode('-', $lastNumber->nomorPengajuanKartu);
            if (count($parts) >= 4) {
                $sequence = (int) $parts[3] + 1;
            }
        }

        $sequencePadded = str_pad($sequence, 4, '0', STR_PAD_LEFT);

        return "PK-{$today}-{$userIdPadded}-{$sequencePadded}";
    }

    private static function sendNotificationToAdmins(PengajuanKartu $pengajuanKartu, string $alasan): void
    {
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'wali_kelas']);
        })->get();

        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('Pengajuan Kartu Baru')
                ->body('User '.Auth::user()->name.' mengajukan kartu baru dengan alasan: '.$alasan)
                ->info()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lihat')
                        ->label('Lihat Detail')
                        ->url(ViewPengajuanKartu::getUrl(['record' => $pengajuanKartu]))
                        ->button(),
                ])
                ->sendToDatabase($admin);
        }
    }
}
