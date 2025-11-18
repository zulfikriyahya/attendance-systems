<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Jabatan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\StatusPulang;
use App\Enums\StatusApproval;
use App\Enums\StatusPresensi;
use App\Models\PresensiSiswa;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use App\Exports\PresensiSiswaExport;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Filament\Resources\PresensiSiswaResource\Pages\EditPresensiSiswa;
use App\Filament\Resources\PresensiSiswaResource\Pages\ViewPresensiSiswa;
use App\Filament\Resources\PresensiSiswaResource\Pages\ListPresensiSiswas;
use App\Filament\Resources\PresensiSiswaResource\Pages\CreatePresensiSiswa;

class PresensiSiswaResource extends Resource
{
    protected static ?string $model = PresensiSiswa::class;

    protected static bool $shouldRegisterNavigation = true; // true

    protected static ?string $navigationGroup = 'Data Siswa';

    protected static ?string $navigationLabel = 'Riwayat Presensi Siswa';

    protected static ?string $recordTitleAttribute = 'siswa_user_name';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'riwayat-siswa';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Presensi Siswa')
                    ->collapsible()
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        Select::make('siswa_id')
                            ->label('Siswa')
                            ->searchable()
                            ->options(function () {
                                return Siswa::with('user')
                                    ->get()
                                    ->pluck('user.name', 'id');
                            })
                            ->disabledOn('edit'),

                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(now())
                            ->disabledOn('edit'),

                        DateTimePicker::make('jamDatang')
                            ->label('Jam Datang')
                            ->displayFormat('H:i:s')
                            ->format('H:i:s')
                            ->withoutDate(),

                        DateTimePicker::make('jamPulang')
                            ->label('Jam Pulang')
                            ->displayFormat('H:i:s')
                            ->format('H:i:s')
                            ->withoutDate(),

                        Select::make('statusPresensi')
                            ->label('Status Presensi')
                            ->native(false)
                            ->options(collect(StatusPresensi::cases())->mapWithKeys(fn ($case) => [$case->value => $case->value])->toArray())
                            ->required(),

                        Select::make('statusPulang')
                            ->label('Status Pulang')
                            ->native(false)
                            ->options(collect(StatusPulang::cases())->mapWithKeys(fn ($case) => [$case->value => $case->value])->toArray()),

                        FileUpload::make('berkasLampiran')
                            ->label('Berkas Lampiran')
                            ->openable(),

                        Select::make('statusApproval')
                            ->label('Status Persetujuan')
                            ->native(false)
                            ->options(
                                collect(StatusApproval::cases())
                                    ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                                    ->toArray()
                            ),

                        Textarea::make('catatan')
                            ->label('Keterangan/Catatan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                // Set Hadir Siswa
                Action::make('set-hadir')
                    ->label('Set Hadir')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->outlined()
                    ->requiresConfirmation()
                    ->form([
                        Radio::make('tipe')
                            ->label('Jenis')
                            ->options([
                                'single' => 'Perorangan',
                                'jabatan' => 'Berdasarkan Jabatan',
                                'all' => 'Semua',
                            ])
                            ->default('single')
                            ->inline()
                            ->reactive()
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        Select::make('namaSiswa')
                            ->label('Nama Siswa')
                            ->options(
                                Siswa::where('status', true)
                                    ->with('user')
                                    ->get()
                                    ->pluck('user.name', 'id')
                            )
                            ->when(Siswa::count() > 10, fn ($field) => $field->searchable())
                            ->preload()
                            ->reactive()
                            ->native(false)
                            ->placeholder('Pilih Siswa')
                            ->required(fn (callable $get) => $get('tipe') === 'single')
                            ->visible(fn (callable $get) => $get('tipe') === 'single'),

                        Select::make('jabatan')
                            ->label('Pilih Jabatan')
                            ->options(
                                Jabatan::orderBy('nama')->pluck('nama', 'id')
                            )
                            ->multiple()
                            ->searchable()
                            ->reactive()
                            ->native(false)
                            ->placeholder('Pilih Jabatan')
                            ->required(fn (callable $get) => $get('tipe') === 'jabatan')
                            ->visible(fn (callable $get) => $get('tipe') === 'jabatan'),

                        DatePicker::make('tanggalMulai')
                            ->label('Tanggal Mulai')
                            ->displayFormat('l, d F Y')
                            ->native(false)
                            ->reactive()
                            ->disabledDates(
                                fn (callable $get) => PresensiSiswa::where('siswa_id', $get('namaSiswa'))
                                    ->where(function ($query) {
                                        $query->where('statusPresensi', StatusPresensi::Libur)
                                            ->orWhere('statusPresensi', StatusPresensi::Cuti)
                                            ->orWhere('statusPresensi', StatusPresensi::Hadir)
                                            ->orWhere('statusPresensi', StatusPresensi::Alfa)
                                            ->orWhere('statusPresensi', StatusPresensi::Terlambat)
                                            ->orWhere('statusPresensi', StatusPresensi::Sakit)
                                            ->orWhere('statusPresensi', StatusPresensi::DinasLuar)
                                            ->orWhere('statusPresensi', StatusPresensi::Dispen)
                                            ->orWhere('statusPresensi', StatusPresensi::Izin);
                                    })
                                    ->pluck('tanggal')
                                    ->map(fn ($tanggal) => Carbon::parse($tanggal)->toDateString())
                                    ->toArray()
                            )
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        DatePicker::make('tanggalSelesai')
                            ->label('Tanggal Selesai')
                            ->displayFormat('l, d F Y')
                            ->native(false)
                            ->reactive()
                            ->disabledDates(
                                fn (callable $get) => PresensiSiswa::where('siswa_id', $get('namaSiswa'))
                                    ->where(function ($query) {
                                        $query->where('statusPresensi', StatusPresensi::Libur)
                                            ->orWhere('statusPresensi', StatusPresensi::Cuti)
                                            ->orWhere('statusPresensi', StatusPresensi::Hadir)
                                            ->orWhere('statusPresensi', StatusPresensi::Alfa)
                                            ->orWhere('statusPresensi', StatusPresensi::Terlambat)
                                            ->orWhere('statusPresensi', StatusPresensi::Sakit)
                                            ->orWhere('statusPresensi', StatusPresensi::DinasLuar)
                                            ->orWhere('statusPresensi', StatusPresensi::Dispen)
                                            ->orWhere('statusPresensi', StatusPresensi::Izin);
                                    })
                                    ->pluck('tanggal')
                                    ->map(fn ($tanggal) => Carbon::parse($tanggal)->toDateString())
                                    ->toArray()
                            )
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TimePicker::make('jamDatang')
                            ->label('Jam Datang')
                            ->seconds(false)
                            ->default('06:45')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TimePicker::make('jamPulang')
                            ->label('Jam Pulang')
                            ->seconds(false)
                            ->default('15:45')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TextArea::make('catatan')
                            ->label('Keterangan')
                            ->placeholder('Misalnya: Hadir normal, Kegiatan khusus, dll.')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),
                    ])
                    ->action(function (array $data) {
                        \App\Jobs\SetHadirSiswa::dispatch($data, Auth::id());

                        Notification::make()
                            ->title('Proses Penetapan Hadir Dijadwalkan')
                            ->body('⏳ Penetapan hadir sedang diproses di background. Anda akan menerima notifikasi setelah selesai.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()->hasRole('super_admin') && Siswa::all()->count() > 0),

                // Set Libur Siswa
                Action::make('set-libur')
                    ->label('Set Libur')
                    ->icon('heroicon-o-calendar-days')
                    ->color(Color::Zinc)
                    ->outlined()
                    ->requiresConfirmation()
                    ->form([
                        Radio::make('tipe')
                            ->label('Jenis')
                            ->options([
                                'single' => 'Perorangan',
                                'jabatan' => 'Berdasarkan Jabatan',
                                'all' => 'Semua',
                            ])
                            ->default('single')
                            ->inline()
                            ->reactive()
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        Select::make('namaSiswa')
                            ->label('Nama Siswa')
                            ->options(
                                Siswa::where('status', true)
                                    ->with('user')
                                    ->get()
                                    ->pluck('user.name', 'id')
                            )
                            ->when(Siswa::count() > 10, fn ($field) => $field->searchable())
                            ->preload()
                            ->reactive()
                            ->native(false)
                            ->placeholder('Pilih Siswa')
                            ->required(fn (callable $get) => $get('tipe') === 'single')
                            ->visible(fn (callable $get) => $get('tipe') === 'single'),

                        Select::make('jabatan')
                            ->label('Pilih Jabatan')
                            ->options(
                                Jabatan::orderBy('nama')->pluck('nama', 'id')
                            )
                            ->multiple()
                            ->searchable()
                            ->reactive()
                            ->native(false)
                            ->placeholder('Pilih Jabatan')
                            ->required(fn (callable $get) => $get('tipe') === 'jabatan')
                            ->visible(fn (callable $get) => $get('tipe') === 'jabatan'),

                        DatePicker::make('tanggalMulai')
                            ->label('Tanggal Mulai')
                            ->displayFormat('l, d F Y')
                            ->minDate(now()->subDay(1))
                            ->maxDate(now()->addMonth(2))
                            ->native(false)
                            ->reactive()
                            ->disabledDates(
                                fn (callable $get) => PresensiSiswa::where('siswa_id', $get('namaSiswa'))
                                    ->where(function ($query) {
                                        $query->where('statusPresensi', StatusPresensi::Libur)
                                            ->orWhere('statusPresensi', StatusPresensi::Cuti)
                                            ->orWhere('statusPresensi', StatusPresensi::Dispen)
                                            ->orWhere('statusPresensi', StatusPresensi::Hadir)
                                            ->orWhere('statusPresensi', StatusPresensi::Alfa)
                                            ->orWhere('statusPresensi', StatusPresensi::Terlambat)
                                            ->orWhere('statusPresensi', StatusPresensi::Sakit)
                                            ->orWhere('statusPresensi', StatusPresensi::DinasLuar)
                                            ->orWhere('statusPresensi', StatusPresensi::Izin);
                                    })

                                    ->pluck('tanggal')
                                    ->map(fn ($tanggal) => Carbon::parse($tanggal)->toDateString())
                                    ->toArray()
                            )
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        DatePicker::make('tanggalSelesai')
                            ->label('Tanggal Selesai')
                            ->displayFormat('l, d F Y')
                            ->minDate(now()->subDay(1))
                            ->maxDate(now()->addMonth(2))
                            ->native(false)
                            ->reactive()
                            ->disabledDates(
                                fn (callable $get) => PresensiSiswa::where('siswa_id', $get('namaSiswa'))
                                    ->where(function ($query) {
                                        $query->where('statusPresensi', StatusPresensi::Libur)
                                            ->orWhere('statusPresensi', StatusPresensi::Cuti)
                                            ->orWhere('statusPresensi', StatusPresensi::Hadir)
                                            ->orWhere('statusPresensi', StatusPresensi::Dispen)
                                            ->orWhere('statusPresensi', StatusPresensi::Alfa)
                                            ->orWhere('statusPresensi', StatusPresensi::Terlambat)
                                            ->orWhere('statusPresensi', StatusPresensi::Sakit)
                                            ->orWhere('statusPresensi', StatusPresensi::DinasLuar)
                                            ->orWhere('statusPresensi', StatusPresensi::Izin);
                                    })

                                    ->pluck('tanggal')
                                    ->map(fn ($tanggal) => Carbon::parse($tanggal)->toDateString())
                                    ->toArray()
                            )
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TextArea::make('catatan')
                            ->label('Keterangan')
                            ->placeholder('Misalnya: Libur Nasional, Libur Guru, dll.')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),
                    ])
                    ->action(function (array $data) {
                        \App\Jobs\SetLiburSiswa::dispatch($data, Auth::id());

                        Notification::make()
                            ->title('Proses Penetapan Libur Dijadwalkan')
                            ->body('⏳ Penetapan libur sedang diproses di background. Anda akan menerima notifikasi setelah selesai.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()->hasRole('super_admin') && Siswa::all()->count() > 0),

                // Set Dispen
                Action::make('set-dispen')
                    ->label('Set Dispen')
                    ->icon('heroicon-o-calendar-days')
                    ->color(Color::Violet)
                    ->outlined()
                    ->requiresConfirmation()
                    ->form([
                        Radio::make('tipe')
                            ->label('Jenis')
                            ->options([
                                'single' => 'Perorangan',
                                'jabatan' => 'Berdasarkan Jabatan',
                                'all' => 'Semua',
                            ])
                            ->default('single')
                            ->inline()
                            ->reactive()
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        Select::make('namaSiswa')
                            ->label('Nama Siswa')
                            ->options(
                                Siswa::where('status', true)
                                    ->with('user')
                                    ->get()
                                    ->pluck('user.name', 'id')
                            )
                            ->when(Siswa::count() > 10, fn ($field) => $field->searchable())
                            ->preload()
                            ->reactive()
                            ->native(false)
                            ->placeholder('Pilih Siswa')
                            ->required(fn (callable $get) => $get('tipe') === 'single')
                            ->visible(fn (callable $get) => $get('tipe') === 'single'),

                        Select::make('jabatan')
                            ->label('Pilih Jabatan')
                            ->options(
                                Jabatan::orderBy('nama')->pluck('nama', 'id')
                            )
                            ->multiple()
                            ->searchable()
                            ->reactive()
                            ->native(false)
                            ->placeholder('Pilih Jabatan')
                            ->required(fn (callable $get) => $get('tipe') === 'jabatan')
                            ->visible(fn (callable $get) => $get('tipe') === 'jabatan'),

                        DatePicker::make('tanggalMulai')
                            ->label('Tanggal Mulai')
                            ->displayFormat('l, d F Y')
                            ->minDate(now()->subDay(1))
                            ->maxDate(now()->addMonth(2))
                            ->native(false)
                            ->reactive()
                            ->disabledDates(
                                fn (callable $get) => PresensiSiswa::where('siswa_id', $get('namaSiswa'))
                                    ->where(function ($query) {
                                        $query->where('statusPresensi', StatusPresensi::Libur)
                                            ->orWhere('statusPresensi', StatusPresensi::Cuti)
                                            ->orWhere('statusPresensi', StatusPresensi::Hadir)
                                            ->orWhere('statusPresensi', StatusPresensi::Alfa)
                                            ->orWhere('statusPresensi', StatusPresensi::Terlambat)
                                            ->orWhere('statusPresensi', StatusPresensi::Sakit)
                                            ->orWhere('statusPresensi', StatusPresensi::Dispen)
                                            ->orWhere('statusPresensi', StatusPresensi::DinasLuar)
                                            ->orWhere('statusPresensi', StatusPresensi::Izin);
                                    })

                                    ->pluck('tanggal')
                                    ->map(fn ($tanggal) => Carbon::parse($tanggal)->toDateString())
                                    ->toArray()
                            )
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        DatePicker::make('tanggalSelesai')
                            ->label('Tanggal Selesai')
                            ->displayFormat('l, d F Y')
                            ->minDate(now()->subDay(1))
                            ->maxDate(now()->addMonth(2))
                            ->native(false)
                            ->reactive()
                            ->disabledDates(
                                fn (callable $get) => PresensiSiswa::where('siswa_id', $get('namaSiswa'))
                                    ->where(function ($query) {
                                        $query->where('statusPresensi', StatusPresensi::Libur)
                                            ->orWhere('statusPresensi', StatusPresensi::Cuti)
                                            ->orWhere('statusPresensi', StatusPresensi::Hadir)
                                            ->orWhere('statusPresensi', StatusPresensi::Alfa)
                                            ->orWhere('statusPresensi', StatusPresensi::Terlambat)
                                            ->orWhere('statusPresensi', StatusPresensi::Sakit)
                                            ->orWhere('statusPresensi', StatusPresensi::Dispen)
                                            ->orWhere('statusPresensi', StatusPresensi::DinasLuar)
                                            ->orWhere('statusPresensi', StatusPresensi::Izin);
                                    })

                                    ->pluck('tanggal')
                                    ->map(fn ($tanggal) => Carbon::parse($tanggal)->toDateString())
                                    ->toArray()
                            )
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TextArea::make('catatan')
                            ->label('Keterangan')
                            ->placeholder('Misalnya: Dispen Lomba, Dispen Latihan.')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),
                    ])
                    ->action(function (array $data) {
                        \App\Jobs\SetDispenSiswa::dispatch($data, Auth::id());

                        Notification::make()
                            ->title('Proses Penetapan Dispen Dijadwalkan')
                            ->body('⏳ Penetapan dispen sedang diproses di background. Anda akan menerima notifikasi setelah selesai.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn () => Auth::user()->hasRole('super_admin') && Siswa::all()->count() > 0),

                // Ekspor Semua Laporan Siswa Berdasarkan Bulan
                Action::make('export')
                    ->label('Ekspor')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->color(Color::Green)
                    ->outlined()
                    ->requiresConfirmation()
                    ->form([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options(
                                collect(range(1, 12))->mapWithKeys(fn ($m) => [
                                    str_pad($m, 2, '0', STR_PAD_LEFT) => Carbon::create()->month($m)->translatedFormat('F'),
                                ])->toArray()
                            )
                            ->required(),
                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->default(now()->year)
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $bulan = $data['bulan'];
                        $tahun = $data['tahun'];

                        return Excel::download(
                            new PresensiSiswaExport($bulan, $tahun),         // ← kirim dua argumen
                            "Rekap Presensi Siswa {$bulan} {$tahun}.xlsx"
                        );
                    })
                    ->visible(fn () => Auth::user()->hasRole('super_admin') && Siswa::all()->count() > 0),

                // TODO: Kirim ke worker
                // Cetak Semua Laporan Siswa Berdasarkan Bulan
                Action::make('print-all')
                    ->label('Cetak')
                    ->color(Color::Cyan)
                    ->icon('heroicon-o-printer')
                    ->outlined()
                    ->requiresConfirmation()
                    ->form([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [
                                $m => Carbon::create()->month($m)->translatedFormat('F'),
                            ])->toArray())
                            ->required(),
                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->default(now()->year)
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $bulan = $data['bulan'];
                        $tahun = $data['tahun'];

                        // Validasi: Cek apakah masih ada status approval pending di bulan dan tahun tersebut
                        $pendingApprovals = PresensiSiswa::whereYear('tanggal', $tahun)
                            ->whereMonth('tanggal', $bulan)
                            ->where('statusApproval', StatusApproval::Pending)
                            ->with(['siswa.user'])
                            ->get();

                        if ($pendingApprovals->isNotEmpty()) {
                            $jumlahPending = $pendingApprovals->count();
                            $namaBulan = Carbon::create()->month((int) $bulan)->translatedFormat('F');

                            // Ambil daftar siswa yang masih pending (maksimal 5 untuk ditampilkan)
                            $daftarSiswa = $pendingApprovals->take(5)
                                ->map(fn ($record) => "• {$record->siswa->user->name}")
                                ->join("\n");

                            $sisaData = $jumlahPending > 5 ? "\n... dan ".($jumlahPending - 5).' siswa lainnya.' : '';

                            // Tampilkan notifikasi error
                            Notification::make()
                                ->title('Laporan Tidak Dapat Dicetak')
                                ->body("❌ Masih terdapat {$jumlahPending} pengajuan ketidakhadiran siswa yang belum diproses untuk bulan {$namaBulan} {$tahun}.\n\nDaftar siswa:\n{$daftarSiswa}{$sisaData}\n\nSilakan proses semua pengajuan terlebih dahulu sebelum mencetak laporan.")
                                ->icon('heroicon-o-exclamation-triangle')
                                ->color('danger')
                                ->persistent() // Notifikasi tidak hilang otomatis
                                ->actions([
                                    NotificationAction::make('lihat_pending')
                                        ->label('Lihat Pengajuan Pending')
                                        ->url(PresensiSiswaResource::getUrl('index', [
                                            'tableFilters' => [
                                                'statusApproval' => ['value' => 'pending'],
                                                'bulan' => ['value' => $bulan],
                                                'tahun' => ['value' => $tahun],
                                            ],
                                        ]))
                                        ->markAsRead()
                                        ->button()
                                        ->color('warning'),
                                ])
                                ->send();

                            return; // Stop eksekusi, tidak lanjut ke print
                        }

                        // Validasi tambahan: Cek apakah ada data di bulan tersebut
                        $totalData = PresensiSiswa::whereYear('tanggal', $tahun)
                            ->whereMonth('tanggal', $bulan)
                            ->count();

                        if ($totalData === 0) {
                            $namaBulan = Carbon::create()->month((int) $bulan)->translatedFormat('F');

                            Notification::make()
                                ->title('Tidak Ada Data')
                                ->body("⚠️ Tidak ditemukan data presensi siswa untuk bulan {$namaBulan} {$tahun}.")
                                ->icon('heroicon-o-information-circle')
                                ->color('warning')
                                ->send();

                            return;
                        }

                        // Jika semua validasi lolos, lanjutkan ke print
                        $namaBulan = Carbon::create()->month((int) $bulan)->translatedFormat('F');

                        Notification::make()
                            ->title('Menyiapkan Laporan')
                            ->body("📄 Laporan presensi siswa untuk bulan {$namaBulan} {$tahun} sedang disiapkan...")
                            ->icon('heroicon-o-document')
                            ->color('info')
                            ->send();

                        $url = route('laporan.all.siswa', [
                            'bulan' => $bulan,
                            'tahun' => $tahun,
                        ]);

                        return redirect($url);
                    })
                    ->visible(fn () => Auth::user()->hasRole('super_admin') && Siswa::all()->count() > 0),

                // Cetak Laporan Mandiri
                Action::make('print-my-report')
                    ->label('Cetak Laporan')
                    ->color(Color::Blue)
                    ->icon('heroicon-o-document-text')
                    ->outlined()
                    ->requiresConfirmation()
                    ->modalHeading('Cetak Laporan Presensi Pribadi')
                    ->modalDescription('Pilih periode laporan presensi yang ingin dicetak')
                    ->modalSubmitActionLabel('Cetak Laporan')
                    ->form([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [
                                $m => Carbon::create()->month($m)->translatedFormat('F'),
                            ])->toArray())
                            ->default(now()->month)
                            ->required()
                            ->helperText('Pilih bulan laporan yang ingin dicetak'),

                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->default(now()->year)
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(now()->year)
                            ->required()
                            ->helperText('Masukkan tahun laporan (2020 - '.now()->year.')'),
                    ])
                    ->action(function (array $data) {
                        $bulan = (int) $data['bulan'];
                        $tahun = (int) $data['tahun'];
                        $user = Auth::user();
                        $siswa = $user->siswa;

                        // Validasi 1: Pastikan user memiliki data siswa
                        if (! $siswa) {
                            Notification::make()
                                ->title('Error: Data Siswa Tidak Ditemukan')
                                ->body('❌ Akun Anda tidak terkait dengan data siswa. Silakan hubungi administrator untuk mengatur data siswa Anda.')
                                ->icon('heroicon-o-exclamation-triangle')
                                ->color('danger')
                                ->persistent()
                                ->send();

                            return;
                        }

                        // Validasi 2: Cek apakah periode sudah berlalu (tidak bisa print bulan yang belum selesai)
                        $sekarang = now();
                        $periodeTarget = Carbon::create($tahun, $bulan, 1)->endOfMonth();

                        if ($periodeTarget->isFuture()) {
                            $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');

                            Notification::make()
                                ->title('Periode Belum Selesai')
                                ->body("⏰ Laporan untuk bulan {$namaBulan} {$tahun} belum dapat dicetak karena periode tersebut belum berakhir. Silakan tunggu hingga bulan berakhir.")
                                ->icon('heroicon-o-clock')
                                ->color('warning')
                                ->send();

                            return;
                        }

                        // Validasi 3: Cek apakah ada data presensi di periode tersebut
                        $totalData = PresensiSiswa::where('siswa_id', $siswa->id)
                            ->whereYear('tanggal', $tahun)
                            ->whereMonth('tanggal', $bulan)
                            ->count();

                        if ($totalData === 0) {
                            $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');

                            Notification::make()
                                ->title('Tidak Ada Data Presensi')
                                ->body("📋 Tidak ditemukan data presensi Anda untuk bulan {$namaBulan} {$tahun}. Pastikan Anda sudah melakukan presensi di periode tersebut.")
                                ->icon('heroicon-o-information-circle')
                                ->color('warning')
                                ->send();

                            return;
                        }

                        // Validasi 4: Cek apakah masih ada pengajuan dengan status pending
                        $pendingApprovals = PresensiSiswa::where('siswa_id', $siswa->id)
                            ->whereYear('tanggal', $tahun)
                            ->whereMonth('tanggal', $bulan)
                            ->where('statusApproval', StatusApproval::Pending)
                            ->orderBy('tanggal', 'asc')
                            ->get();

                        if ($pendingApprovals->isNotEmpty()) {
                            $jumlahPending = $pendingApprovals->count();
                            $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');

                            // Ambil maksimal 5 tanggal untuk ditampilkan
                            $daftarTanggal = $pendingApprovals->take(5)
                                ->map(fn ($record) => '• '.$record->tanggal->translatedFormat('d F Y').
                                    " ({$record->statusPresensi->label()})")
                                ->join("\n");

                            $sisaData = $jumlahPending > 5 ?
                                "\n... dan ".($jumlahPending - 5).' pengajuan lainnya.' : '';

                            Notification::make()
                                ->title('Laporan Tidak Dapat Dicetak')
                                ->body("❌ Anda masih memiliki {$jumlahPending} pengajuan ketidakhadiran yang belum diproses untuk bulan {$namaBulan} {$tahun}.\n\n📅 Daftar pengajuan pending:\n{$daftarTanggal}{$sisaData}\n\n⏳ Silakan tunggu administrator memproses pengajuan Anda terlebih dahulu sebelum mencetak laporan.")
                                ->icon('heroicon-o-exclamation-triangle')
                                ->color('danger')
                                ->persistent()
                                ->actions([
                                    NotificationAction::make('lihat_pengajuan')
                                        ->label('Lihat Pengajuan Pending')
                                        ->url(PresensiSiswaResource::getUrl('index', [
                                            'tableFilters' => [
                                                'statusApproval' => ['value' => 'pending'],
                                                'siswa' => ['value' => $siswa->id],
                                                'bulan' => ['value' => $bulan],
                                                'tahun' => ['value' => $tahun],
                                            ],
                                        ]))
                                        ->markAsRead()
                                        ->button()
                                        ->color('warning'),

                                    NotificationAction::make('tutup')
                                        ->label('Tutup')
                                        ->markAsRead()
                                        ->color('gray'),
                                ])
                                ->send();

                            return;
                        }

                        // Validasi 5: Cek apakah ada pengajuan yang ditolak dan belum diperbaiki
                        $rejectedApprovals = PresensiSiswa::where('siswa_id', $siswa->id)
                            ->whereYear('tanggal', $tahun)
                            ->whereMonth('tanggal', $bulan)
                            ->where('statusApproval', StatusApproval::Rejected)
                            ->count();

                        if ($rejectedApprovals > 0) {
                            $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');

                            Notification::make()
                                ->title('Peringatan: Ada Pengajuan Ditolak')
                                ->body("⚠️ Terdapat {$rejectedApprovals} pengajuan ketidakhadiran yang ditolak untuk bulan {$namaBulan} {$tahun}. Laporan tetap dapat dicetak, namun pastikan untuk menindaklanjuti pengajuan yang ditolak.")
                                ->icon('heroicon-o-exclamation-circle')
                                ->color('warning')
                                ->actions([
                                    NotificationAction::make('lihat_ditolak')
                                        ->label('Lihat Pengajuan Ditolak')
                                        ->url(PresensiSiswaResource::getUrl('index', [
                                            'tableFilters' => [
                                                'statusApproval' => ['value' => 'rejected'],
                                                'siswa' => ['value' => $siswa->id],
                                                'bulan' => ['value' => $bulan],
                                                'tahun' => ['value' => $tahun],
                                            ],
                                        ]))
                                        ->markAsRead()
                                        ->button()
                                        ->color('danger'),
                                ])
                                ->send();
                        }

                        // Jika semua validasi lolos, lanjutkan ke print
                        $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');
                        $namaSiswa = $user->name;

                        // Ambil ringkasan data untuk notifikasi
                        $totalHadir = PresensiSiswa::where('siswa_id', $siswa->id)
                            ->whereYear('tanggal', $tahun)
                            ->whereMonth('tanggal', $bulan)
                            ->where('statusPresensi', StatusPresensi::Hadir)
                            ->count();

                        $totalTerlambat = PresensiSiswa::where('siswa_id', $siswa->id)
                            ->whereYear('tanggal', $tahun)
                            ->whereMonth('tanggal', $bulan)
                            ->where('statusPresensi', StatusPresensi::Terlambat)
                            ->count();

                        Notification::make()
                            ->title('Menyiapkan Laporan Presensi')
                            ->body("📄 Laporan presensi atas nama {$namaSiswa} untuk bulan {$namaBulan} {$tahun} sedang disiapkan...\n\n📊 Ringkasan: {$totalHadir} hadir, {$totalTerlambat} terlambat dari {$totalData} hari kerja.")
                            ->icon('heroicon-o-document')
                            ->color('success')
                            ->duration(5000)
                            ->send();

                        // Redirect ke route laporan
                        $url = route('laporan.single.siswa', [
                            'siswa' => $siswa->id,  // Sesuaikan dengan parameter route
                            'bulan' => $bulan,
                            'tahun' => $tahun,
                        ]);

                        return redirect($url);
                    })
                    ->visible(function () {
                        $user = Auth::user();

                        // Hanya tampil untuk user yang bukan super_admin dan memiliki data siswa
                        return ! $user->hasRole('super_admin') && $user->siswa !== null;
                    }),

                // Pengajuan Ketidakhadiran
                Action::make('ajukan-izin')
                    ->label('Ajukan Ketidakhadiran')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->outlined()
                    ->requiresConfirmation()
                    ->visible(function () {
                        return ! Auth::user()->hasRole('super_admin');
                    })
                    ->form([
                        Select::make('statusPresensi')
                            ->label('Jenis Ketidakhadiran')
                            ->options([
                                StatusPresensi::Sakit->value => 'Sakit',
                                StatusPresensi::Izin->value => 'Izin',
                                StatusPresensi::Dispen->value => 'Dispensasi',
                            ])
                            ->required()
                            ->reactive()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        DatePicker::make('tanggalMulai')
                            ->label('Tanggal Mulai')
                            ->displayFormat('l, d F Y')
                            ->minDate(now(-3))
                            ->maxDate(now()->addMonth(3))
                            ->native(false)
                            ->reactive()
                            ->disabledDates(function () {
                                $siswaId = Auth::user()->siswa?->id;

                                if (! $siswaId) {
                                    return [];
                                }

                                return PresensiSiswa::where('siswa_id', $siswaId)
                                    ->where(function ($query) {
                                        $query->where('statusPresensi', StatusPresensi::Libur)
                                            ->orWhere('statusPresensi', StatusPresensi::Hadir)
                                            ->orWhere('statusPresensi', StatusPresensi::Alfa)
                                            ->orWhere('statusPresensi', StatusPresensi::Terlambat)
                                            ->orWhere('statusPresensi', StatusPresensi::Sakit)
                                            ->orWhere('statusPresensi', StatusPresensi::Dispen)
                                            ->orWhere('statusPresensi', StatusPresensi::Izin);
                                    })
                                    ->pluck('tanggal')
                                    ->map(fn ($tanggal) => Carbon::parse($tanggal)->toDateString())
                                    ->toArray();
                            })
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        DatePicker::make('tanggalSelesai')
                            ->label('Tanggal Selesai')
                            ->displayFormat('l, d F Y')
                            ->minDate(fn (callable $get) => $get('tanggalMulai') ? Carbon::parse($get('tanggalMulai')) : now())
                            ->maxDate(now()->addMonth(3))
                            ->native(false)
                            ->reactive()
                            ->disabledDates(function () {
                                $siswaId = Auth::user()->siswa?->id;

                                if (! $siswaId) {
                                    return [];
                                }

                                return PresensiSiswa::where('siswa_id', $siswaId)
                                    ->where(function ($query) {
                                        $query->where('statusPresensi', StatusPresensi::Libur)
                                            ->orWhere('statusPresensi', StatusPresensi::Hadir)
                                            ->orWhere('statusPresensi', StatusPresensi::Alfa)
                                            ->orWhere('statusPresensi', StatusPresensi::Terlambat)
                                            ->orWhere('statusPresensi', StatusPresensi::Sakit)
                                            ->orWhere('statusPresensi', StatusPresensi::Dispen)
                                            ->orWhere('statusPresensi', StatusPresensi::Izin);
                                    })
                                    ->pluck('tanggal')
                                    ->map(fn ($tanggal) => Carbon::parse($tanggal)->toDateString())
                                    ->toArray();
                            })
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        Textarea::make('catatan')
                            ->label('Keterangan')
                            ->placeholder('Jelaskan alasan ketidakhadiran Anda...')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        FileUpload::make('berkasLampiran')
                            ->label('Lampiran Pendukung')
                            ->directory('surat-keterangan-siswa')
                            ->maxSize(2048) // 2MB
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->helperText('Format yang diterima: PDF, JPG, PNG. Maksimal 2MB.'),
                            // ->required(fn (callable $get) => in_array($get('statusPresensi'), [
                            //     StatusPresensi::Sakit->value,
                            //     StatusPresensi::Izin->value,
                            //     StatusPresensi::Dispen->value,
                            // ]))
                            // ->validationMessages([
                            //     'required' => 'Lampiran wajib dilampirkan untuk jenis ketidakhadiran ini.',
                            // ]),
                    ])
                    ->action(function (array $data) {
                        $siswaId = Auth::user()->siswa?->id;

                        if (! $siswaId) {
                            Notification::make()
                                ->title('Error')
                                ->body('Data siswa tidak ditemukan.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $tanggalMulai = Carbon::parse($data['tanggalMulai']);
                        $tanggalSelesai = Carbon::parse($data['tanggalSelesai']);

                        // Validasi tanggal
                        if ($tanggalSelesai->lt($tanggalMulai)) {
                            Notification::make()
                                ->title('Error')
                                ->body('Tanggal selesai tidak boleh lebih awal dari tanggal mulai.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Generate range tanggal
                        $rangeTanggal = collect();
                        for ($date = $tanggalMulai->copy(); $date->lte($tanggalSelesai); $date->addDay()) {
                            $rangeTanggal->push($date->format('Y-m-d'));
                        }

                        $jumlahBerhasil = 0;
                        $jumlahDiabaikan = 0;
                        $createdRecords = collect();

                        // Loop untuk setiap tanggal dalam range
                        foreach ($rangeTanggal as $tanggal) {
                            $sudahAda = PresensiSiswa::where('siswa_id', $siswaId)
                                ->whereDate('tanggal', $tanggal)
                                ->exists();

                            if (! $sudahAda) {
                                $record = PresensiSiswa::create([
                                    'siswa_id' => $siswaId,
                                    'tanggal' => $tanggal,
                                    'statusPresensi' => $data['statusPresensi'],
                                    'catatan' => $data['catatan'],
                                    'berkasLampiran' => $data['berkasLampiran'] ?? null,
                                    'statusApproval' => StatusApproval::Pending,
                                ]);

                                $createdRecords->push($record);
                                $jumlahBerhasil++;
                            } else {
                                $jumlahDiabaikan++;
                            }
                        }

                        // Load relasi untuk notifikasi
                        $createdRecords->each(function ($record) {
                            $record->load('siswa.user');
                        });

                        // Notifikasi ke pengguna yang mengajukan
                        $totalHari = $rangeTanggal->count();
                        $jenisKetidakhadiran = match ($data['statusPresensi']) {
                            StatusPresensi::Sakit->value => 'Sakit',
                            StatusPresensi::Izin->value => 'Izin',
                            StatusPresensi::Dispen->value => 'Dispensasi',
                            default => 'Ketidakhadiran'
                        };

                        Notification::make()
                            ->title('Pengajuan ketidakhadiran berhasil dikirim')
                            ->body("📅 {$jenisKetidakhadiran} untuk {$totalHari} hari. 🟢 {$jumlahBerhasil} berhasil, 🔴 {$jumlahDiabaikan} diabaikan.")
                            ->success()
                            ->send();

                        // Notifikasi ke semua admin (super_admin)
                        if ($createdRecords->isNotEmpty()) {
                            $firstRecord = $createdRecords->first();
                            $namaJenis = match ($data['statusPresensi']) {
                                StatusPresensi::Sakit->value => 'Sakit',
                                StatusPresensi::Izin->value => 'Izin',
                                StatusPresensi::Dispen->value => 'Dispensasi',
                                default => 'Ketidakhadiran'
                            };

                            User::role('super_admin')->get()->each(function ($admin) use ($firstRecord, $totalHari, $tanggalMulai, $tanggalSelesai, $namaJenis) {
                                $periodeText = $totalHari > 1
                                    ? "periode {$tanggalMulai->translatedFormat('d M Y')} - {$tanggalSelesai->translatedFormat('d M Y')} ({$totalHari} hari)"
                                    : "tanggal {$tanggalMulai->translatedFormat('l, d F Y')}";

                                Notification::make()
                                    ->title("Pengajuan {$namaJenis} Baru")
                                    ->body("Siswa {$firstRecord->siswa?->user?->name} mengajukan {$namaJenis} untuk {$periodeText}")
                                    ->icon('heroicon-o-exclamation-circle')
                                    ->color('warning')
                                    ->actions([
                                        NotificationAction::make('lihat')
                                            ->label('Lihat Detail')
                                            ->url(PresensiSiswaResource::getUrl('index', [
                                                'tableFilters' => [
                                                    'statusApproval' => ['value' => 'pending'],
                                                ],
                                            ]))
                                            ->markAsRead()
                                            ->button()
                                            ->color('primary'),
                                    ])
                                    ->sendToDatabase($admin);
                            });
                        }
                    }),

            ])
            ->columns([
                ImageColumn::make('siswa.user.avatar')
                    ->disabledClick()
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('siswa.user.name')
                    ->disabledClick()
                    ->label('Nama Lengkap')
                    ->searchable(PresensiSiswa::count() > 10),
                TextColumn::make('tanggal')
                    ->disabledClick()
                    ->label('Tanggal')
                    ->sortable('desc')
                    ->date('l, d F Y'),
                TextColumn::make('jamDatang')
                    ->disabledClick()
                    ->label('Jam Datang')
                    ->sortable('desc')
                    ->dateTime('H:i:s'),
                TextColumn::make('jamPulang')
                    ->disabledClick()
                    ->label('Jam Pulang')
                    ->dateTime('H:i:s'),
                TextColumn::make('statusPresensi')
                    ->disabledClick()
                    ->label('Status Presensi')
                    ->sortable()
                    ->formatStateUsing(fn (StatusPresensi $state) => $state->label())
                    ->badge()
                    ->color(fn (StatusPresensi $state): string => match ($state) {
                        StatusPresensi::Hadir => 'success',
                        StatusPresensi::Alfa => 'danger',
                        StatusPresensi::Libur => 'gray',
                        StatusPresensi::Cuti => 'primary',
                        default => 'warning',
                    }),
                TextColumn::make('statusPulang')
                    ->disabledClick()
                    ->label('Status Pulang')
                    ->sortable()
                    ->formatStateUsing(fn (StatusPulang $state) => $state->label())
                    ->badge()
                    ->color(fn (StatusPulang $state): string => match ($state) {
                        StatusPulang::Pulang => 'success',
                        StatusPulang::Mangkir => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('statusApproval')
                    ->disabledClick()
                    ->label('Status Persetujuan')
                    ->sortable()
                    ->formatStateUsing(fn (StatusApproval $state) => $state->label())
                    ->badge()
                    ->color(fn (StatusApproval $state): string => match ($state) {
                        StatusApproval::Approved => 'success',
                        StatusApproval::Pending => 'warning',
                        StatusApproval::Rejected => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('catatan')
                    ->disabledClick()
                    ->wrap()
                    ->label('Keterangan'),
            ])
            ->paginationPageOptions([5, 10, 25])
            ->filters([
                SelectFilter::make('statusApproval')
                    ->label('Status Persetujuan')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->visible(Auth::user()->hasAnyRole(['super_admin', 'wali_kelas']) && Siswa::all()->count() > 0),
                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        '1' => 'Januari',
                        '2' => 'Februari',
                        '3' => 'Maret',
                        '4' => 'April',
                        '5' => 'Mei',
                        '6' => 'Juni',
                        '7' => 'Juli',
                        '8' => 'Agustus',
                        '9' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value']) || blank($data['value'])) {
                            return $query; // Jangan filter kalau belum dipilih
                        }

                        return Schema::getConnection()->getDriverName() === 'sqlite'
                            ? $query->whereRaw('CAST(strftime("%m", tanggal) AS INTEGER) = ?', [$data['value']])
                            : $query->whereMonth('tanggal', $data['value']);
                    })
                    ->visible(Auth::user()->hasAnyRole(['super_admin', 'wali_kelas']) && Siswa::all()->count() > 0),

                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function () {
                        if (Schema::getConnection()->getDriverName() === 'sqlite') {
                            return PresensiSiswa::selectRaw('DISTINCT CAST(strftime("%Y", tanggal) AS INTEGER) as tahun')
                                ->orderBy('tahun', 'desc')
                                ->pluck('tahun', 'tahun');
                        }

                        return PresensiSiswa::selectRaw('DISTINCT YEAR(tanggal) as tahun')
                            ->orderBy('tahun', 'desc')
                            ->pluck('tahun', 'tahun');
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value']) || blank($data['value'])) {
                            return $query; // Jangan filter kalau belum dipilih
                        }

                        return Schema::getConnection()->getDriverName() === 'sqlite'
                            ? $query->whereRaw('strftime("%Y", tanggal) = ?', [$data['value']])
                            : $query->whereYear('tanggal', $data['value']);
                    })
                    ->visible(Auth::user()->hasAnyRole(['super_admin', 'wali_kelas']) && Siswa::all()->count() > 0),
                TrashedFilter::make()
                    ->visible(Auth::user()->hasAnyRole(['super_admin', 'wali_kelas']) && Siswa::all()->count() > 0),
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
                    Action::make('approve')
                        ->label('Setujui')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn ($record) => Auth::user()->hasRole('super_admin') && $record->statusApproval?->value === StatusApproval::Pending->value)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'statusApproval' => StatusApproval::Approved,
                            ]);

                            // Load relasi untuk notifikasi
                            $record->load('siswa.user');

                            // Notifikasi ke siswa yang mengajukan
                            $jenisKetidakhadiran = collect(StatusPresensi::cases())
                                ->firstWhere('value', $record->statusPresensi)
                                ->label();

                            Notification::make()
                                ->title('Pengajuan Ketidakhadiran Disetujui')
                                ->body("✅ Pengajuan {$jenisKetidakhadiran} Anda pada tanggal {$record->tanggal->translatedFormat('l, d F Y')} telah disetujui.")
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->sendToDatabase($record->siswa->user);

                            // Notifikasi ke admin yang menyetujui
                            Notification::make()
                                ->title('Pengajuan berhasil disetujui')
                                ->body("Pengajuan {$jenisKetidakhadiran} dari {$record->siswa->user->name} telah disetujui.")
                                ->success()
                                ->send();
                        }),

                    Action::make('reject')
                        ->label('Tolak')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn ($record) => Auth::user()->hasRole('super_admin') && $record->statusApproval?->value === StatusApproval::Pending->value)
                        ->form([
                            Textarea::make('alasanPenolakan')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan pengajuan ini...')
                                ->required()
                                ->validationMessages([
                                    'required' => 'Alasan penolakan wajib diisi.',
                                ]),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Pengajuan')
                        ->modalDescription('Data pengajuan akan dihapus permanen setelah ditolak. Pastikan Anda sudah memberikan alasan yang jelas.')
                        ->modalSubmitActionLabel('Ya, Tolak dan Hapus')
                        ->action(function ($record, array $data) {
                            // Simpan data untuk notifikasi sebelum dihapus
                            $record->load('siswa.user');
                            $siswaUser = $record->siswa->user;
                            $tanggal = $record->tanggal;
                            $jenisKetidakhadiran = collect(StatusPresensi::cases())
                                ->firstWhere('value', $record->statusPresensi)
                                ->label();
                            $alasanPenolakan = $data['alasanPenolakan'];

                            // Hapus data pengajuan
                            $record->delete();

                            // Notifikasi ke siswa yang mengajukan
                            Notification::make()
                                ->title('Pengajuan Ketidakhadiran Ditolak')
                                ->body("❌ Pengajuan {$jenisKetidakhadiran} Anda pada tanggal {$tanggal->translatedFormat('l, d F Y')} ditolak dan data pengajuan telah dihapus.\n\nAlasan: {$alasanPenolakan}\n\nAnda dapat mengajukan ulang dengan memperbaiki dokumen atau alasan yang diperlukan.")
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->sendToDatabase($siswaUser);

                            // Notifikasi ke admin yang menolak
                            Notification::make()
                                ->title('Pengajuan berhasil ditolak dan dihapus')
                                ->body("Pengajuan {$jenisKetidakhadiran} dari {$siswaUser->name} telah ditolak dan data dihapus.")
                                ->warning()
                                ->send();
                        }),
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
            'index' => ListPresensiSiswas::route('/'),
            // 'view' => ViewPresensiSiswa::route('/{record}'),
            'create' => CreatePresensiSiswa::route('/create'),
            'edit' => EditPresensiSiswa::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->orderBy('tanggal', 'desc')
            ->orderBy('jamDatang', 'asc');

        if (! Auth::user()->hasRole(['super_admin', 'administrator'])) {
            $siswaId = Auth::user()->siswa?->id;

            if ($siswaId) {
                $query->where('siswa_id', $siswaId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }
}
