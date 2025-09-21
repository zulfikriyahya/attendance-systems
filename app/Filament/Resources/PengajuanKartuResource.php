<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanKartuResource\Pages;
use App\Models\Instansi;
use App\Models\PengajuanKartu;
use App\Models\User;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
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
                Select::make('user_id')
                    ->label('Pengguna')
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

                TextInput::make('biaya')
                    ->label('Biaya Pembuatan Kartu')
                    ->prefix('Rp.')
                    ->integer()
                    ->default(getenv('BIAYA_KARTU'))
                    ->maxValue(50000)
                    ->maxLength(5)
                    ->minLength(1)
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi',
                    ])
                    ->placeholder('Gratis = 0')
                    ->disabled(fn () => ! Auth::user()->hasRole(['super_admin', 'wali_kelas'])),

                DateTimePicker::make('tanggalPengajuanKartu')
                    ->label('Tanggal Pengajuan')
                    ->required()
                    ->displayFormat('l, d F Y H:i')
                    ->native(false)
                    ->default(now())
                    ->maxDate(now()),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Proses' => 'Proses',
                        'Selesai' => 'Selesai',
                    ])
                    ->default('Pending')
                    ->required()
                    ->disabled(fn () => ! Auth::user()->hasRole(['super_admin', 'wali_kelas'])),

                Textarea::make('alasanPengajuanKartu')
                    ->label('Alasan Pengajuan')
                    ->required()
                    ->columnSpanFull()
                    ->placeholder('Contoh: Kartu hilang, kartu rusak, dll.')
                    ->rows(3),
            ])->columns(3);
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
                            ->default(getenv('BIAYA_KARTU'))
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
                    ->label('Nomor Pengajuan')
                    ->searchable(PengajuanKartu::all()->count() > 10)
                    ->sortable()
                    ->copyable(),

                TextColumn::make('user.name')
                    ->label('Nama User')
                    ->searchable(PengajuanKartu::all()->count() > 10)
                    ->sortable(),

                TextColumn::make('tanggalPengajuanKartu')
                    ->label('Tanggal Pengajuan')
                    ->date('l, d F Y')
                    ->sortable(),

                TextColumn::make('alasanPengajuanKartu')
                    ->label('Alasan Pengajuan')
                    ->limit(15)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 15) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'Pending',
                        'info' => 'Proses',
                        'success' => 'Selesai',
                    ])
                    ->sortable(),

                ToggleColumn::make('statusAmbil')
                    ->label('Penyerahan')
                    ->disabledClick(fn ($record) => $record->status === 'Selesai' && Auth::user()->hasRole('super_admin')),
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
                EditAction::make()
                    ->button()
                    ->outlined()
                    ->visible(fn () => Auth::user()->hasRole('super_admin')),

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

                        Notification::make()
                            ->title('Status Diperbarui')
                            ->body('Pengajuan telah disetujui dan status diubah ke Proses.')
                            ->success()
                            ->send();

                        // Notifikasi ke user
                        Notification::make()
                            ->title('Pengajuan kartu Anda sedang diproses.')
                            ->body('Pengajuan kartu Anda dengan nomor '.$record->nomorPengajuanKartu.' sedang diproses.')
                            ->success()
                            ->sendToDatabase($record->user);

                        // Kirim ke WhatsApp
                        $phoneNumber = null;
                        $userName = $record->user->name;

                        // Cek apakah user adalah siswa atau pegawai
                        if ($record->user->siswa) {
                            $phoneNumber = $record->user->siswa->telepon;
                        } elseif ($record->user->pegawai) {
                            $phoneNumber = $record->user->pegawai->telepon;
                        }

                        if ($phoneNumber) {
                            $whatsappService = new WhatsappService;
                            $tahunIni = date('Y');
                            $namaInstansi = Instansi::all()->first()->nama;
                            $instansi = strtoupper($namaInstansi);
                            $url = config('app.url');
                            $message = <<<TEXT
                            *PTSP {$instansi}*
                            
                            ———————————————————
                            🪪 *Kartu Presensi Sedang Diproses*
                            ———————————————————
                            Halo {$userName},
                            Pengajuan kartu Anda dengan nomor *{$record->nomorPengajuanKartu}* sedang diproses.
                            Mohon menunggu kabar selanjutnya.
                            
                            Terima kasih! 🙏
                            ———————————————————
                            Tautan : {$url}/admin/pengajuan-kartu/{$record->id}
                            ———————————————————
                            
                            *© 2022 - {$tahunIni} {$instansi}*
                            TEXT;
                            $whatsappService->send($phoneNumber, $message);
                        }
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

                        Notification::make()
                            ->title('Pengajuan Selesai')
                            ->body('Pengajuan kartu telah diselesaikan.')
                            ->success()
                            ->send();

                        // Notifikasi ke user
                        Notification::make()
                            ->title('Kartu Siap Diambil di Ruang PTSP')
                            ->body('Pengajuan kartu Anda dengan nomor '.$record->nomorPengajuanKartu.' telah selesai diproses. (Biaya pembuatan kartu Rp. '.number_format($record->biaya, 0, ',', '.').')')
                            ->success()
                            ->sendToDatabase($record->user);

                        // Kirim ke WhatsApp
                        $phoneNumber = null;
                        $userName = $record->user->name;

                        // Cek apakah user adalah siswa atau pegawai
                        if ($record->user->siswa) {
                            $phoneNumber = $record->user->siswa->telepon;
                        } elseif ($record->user->pegawai) {
                            $phoneNumber = $record->user->pegawai->telepon;
                        }

                        if ($phoneNumber) {
                            $whatsappService = new WhatsappService;
                            $biaya = number_format($record->biaya, 0, ',', '.');
                            $tahunIni = date('Y');
                            $namaInstansi = Instansi::all()->first()->nama;
                            $instansi = strtoupper($namaInstansi);
                            $url = config('app.url');
                            $message = <<<TEXT
                            *PTSP {$instansi}*
                            
                            ———————————————————
                            🪪 *Kartu Siap Diambil di Ruang PTSP*
                            ———————————————————
                            Halo {$userName},
                            Pengajuan kartu Anda dengan nomor *{$record->nomorPengajuanKartu}* telah selesai diproses.
                            🏢 Silakan ambil di Ruang PTSP
                            💸 Biaya pembuatan kartu: Rp. *{$biaya}*,-
                            
                            Terima kasih! 🙏
                            ———————————————————
                            Tautan : {$url}/admin/pengajuan-kartu/{$record->id}
                            ———————————————————
                            
                            *© 2022 - {$tahunIni} {$instansi}*
                            TEXT;
                            $whatsappService->send($phoneNumber, $message);
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole(['super_admin'])),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole(['super_admin'])),

                    RestoreBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole(['super_admin'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuanKartus::route('/'),
            'create' => Pages\CreatePengajuanKartu::route('/create'),
            'view' => Pages\ViewPengajuanKartu::route('/{record}'),
            'edit' => Pages\EditPengajuanKartu::route('/{record}/edit'),
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
                        ->url(Pages\ViewPengajuanKartu::getUrl(['record' => $pengajuanKartu]))
                        ->button(),
                ])
                ->sendToDatabase($admin);
        }
    }
}
