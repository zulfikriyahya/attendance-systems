<?php

namespace App\Filament\Resources;

use App\Filament\Imports\PegawaiImporter;
use App\Filament\Resources\PegawaiResource\Pages\CetakKartuPegawai;
use App\Filament\Resources\PegawaiResource\Pages\CreatePegawai;
use App\Filament\Resources\PegawaiResource\Pages\EditPegawai;
use App\Filament\Resources\PegawaiResource\Pages\ListPegawais;
use App\Filament\Resources\PegawaiResource\Pages\ViewPegawai;
use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\Siswa;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PegawaiResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Data Pegawai';

    protected static ?string $navigationLabel = 'Pegawai';

    protected static ?string $recordTitleAttribute = 'user_name';

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
                                $q->whereIn('name', ['administrator', 'siswa']);
                            })
                            ->pluck('name', 'id');
                    })
                    ->when(User::count() > 10, fn ($field) => $field->searchable())
                    ->preload()
                    ->disabledOn('edit')
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                TextInput::make('rfid')
                    ->label('RFID')
                    ->unique(ignoreRecord: true)
                    ->numeric()
                    ->required()
                    ->autofocus()
                    ->minLength(10)
                    ->maxLength(10)
                    ->validationMessages([
                        'unique' => 'RFID sudah pernah dipakai.',
                        'required' => 'Form ini wajib diisi.',
                        'min_digits' => 'Minimal 10 digit.',
                        'max_digits' => 'Maksimal 10 digit.',
                    ]),
                TextInput::make('nip')
                    ->label('NIK/NIP/NUPTK')
                    ->unique(ignoreRecord: true)
                    ->numeric()
                    ->required()
                    ->minLength(16)
                    ->maxLength(18)
                    ->validationMessages([
                        'unique' => 'NIK/NIP/NUPTK sudah pernah dipakai.',
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
                    ->placeholder('Cth: 08**********')
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
                    ->when(Jabatan::count() > 10, fn ($field) => $field->searchable())
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
                ActionGroup::make([
                    ImportAction::make('import')
                        ->label('Impor Data')
                        ->outlined()
                        ->color('primary')
                        ->icon('heroicon-o-identification')
                        ->importer(PegawaiImporter::class)
                        ->visible(fn () => Auth::user()->hasRole('super_admin')),
                    Action::make('import-kartu')
                        ->label('Impor Kartu')
                        ->outlined()
                        ->color('primary')
                        ->icon('heroicon-o-photo')
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::user()->hasRole('super_admin'))
                        ->form([
                            FileUpload::make('zip_file')
                                ->label('File ZIP Kartu Pegawai')
                                ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                                ->required()
                                ->helperText('Upload file ZIP yang berisi kartu pegawai')
                                ->maxSize(1024000),

                            Checkbox::make('overwrite_existing')
                                ->label('Timpa file yang sudah ada')
                                ->default(true),

                            Checkbox::make('preserve_structure')
                                ->label('Pertahankan struktur folder dalam ZIP')
                                ->default(true)
                                ->helperText('Jika dicentang, struktur folder dalam ZIP akan dipertahankan'),
                        ])
                        ->action(function (array $data) {
                            self::extractZipToStorage($data);
                        }),
                ])
                    ->hiddenLabel()
                    ->icon('heroicon-o-rectangle-group')
                    ->color(Color::Emerald),
                // ->button()
                // ->outlined()
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
                    ->color(fn (string $state): string => match ($state) {
                        'Pria' => 'success',
                        'Wanita' => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state) {
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
                TrashedFilter::make()
                    ->visible(Auth::user()->hasRole('super_admin') && Siswa::all()->count() > 0)
                    ->native(false),
            ])
            ->actions([
                ActionGroup::make([
                    // ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
                Action::make('download')
                    ->label('Kartu')
                    ->button()
                    ->outlined()
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color(Color::Fuchsia)
                    ->url(function ($record) {
                        $filename = basename($record->user->avatar);
                        $path = "kartu/{$filename}";

                        if (Storage::disk('public')->exists($path)) {
                            return asset("storage/{$path}");
                        }

                        return null;
                    })
                    ->hidden(fn ($record) => ! Storage::disk('public')->exists('kartu/'.basename($record->user->avatar)))
                    ->openUrlInNewTab(true),
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
            'print' => CetakKartuPegawai::route('/{record}/print'),
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

    protected static function extractZipToStorage(array $data): void
    {
        try {
            $disk = Storage::disk('public');
            $relativePath = $data['zip_file'];
            $zipFilePath = $disk->path($relativePath);

            $extractFolder = 'kartu';
            $overwrite = $data['overwrite_existing'];
            $preserveStructure = $data['preserve_structure'];

            if (! file_exists($zipFilePath)) {
                Notification::make()
                    ->title('Error')
                    ->body("File ZIP tidak ditemukan: {$relativePath}")
                    ->danger()
                    ->send();

                return;
            }

            $zip = new \ZipArchive;
            $result = $zip->open($zipFilePath);

            if ($result !== true) {
                Notification::make()
                    ->title('Error')
                    ->body("Tidak dapat membuka file ZIP. Error code: {$result}")
                    ->danger()
                    ->send();

                return;
            }

            $extractedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $errors = [];

            // Destination folder
            $destinationPath = $disk->path($extractFolder);
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Extract files
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Skip directories and system files
                if (substr($filename, -1) === '/' || strpos($filename, '__MACOSX') !== false) {
                    continue;
                }

                try {
                    // Determine final filename
                    $finalFilename = $preserveStructure ? $filename : basename($filename);
                    $finalPath = $destinationPath.'/'.$finalFilename;

                    // Create subdirectory if needed
                    if ($preserveStructure && strpos($finalFilename, '/') !== false) {
                        $subDir = dirname($finalPath);
                        if (! file_exists($subDir)) {
                            mkdir($subDir, 0755, true);
                        }
                    }

                    // Check if file exists
                    if (file_exists($finalPath) && ! $overwrite) {
                        $skippedCount++;

                        continue;
                    }

                    // Extract file
                    $fileContent = $zip->getFromIndex($i);
                    if ($fileContent !== false) {
                        if (file_put_contents($finalPath, $fileContent) !== false) {
                            $extractedCount++;
                        } else {
                            $errors[] = 'Gagal menyimpan: '.$filename;
                            $errorCount++;
                        }
                    } else {
                        $errors[] = 'Gagal membaca dari ZIP: '.$filename;
                        $errorCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error pada {$filename}: ".$e->getMessage();
                    $errorCount++;
                }
            }

            $zip->close();

            // Clean up ZIP file
            $disk->delete($relativePath);

            // Show result
            self::showExtractionResult($extractedCount, $skippedCount, $errorCount, $errors, $extractFolder);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Terjadi kesalahan: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function showExtractionResult(
        int $extracted,
        int $skipped,
        int $errors,
        array $errorMessages,
        string $folder
    ): void {
        $message = "Ekstrak ZIP selesai!\n\n";
        $message .= "📁 Folder tujuan: storage/app/public/{$folder}\n";
        $message .= "✅ File berhasil diekstrak: {$extracted}\n";

        if ($skipped > 0) {
            $message .= "⏭️ File dilewati: {$skipped}\n";
        }

        if ($errors > 0) {
            $message .= "❌ File error: {$errors}\n";
            if (! empty($errorMessages)) {
                $message .= "\nDetail error:\n".implode("\n", array_slice($errorMessages, 0, 5));
                if (count($errorMessages) > 5) {
                    $message .= "\n... dan ".(count($errorMessages) - 5).' error lainnya';
                }
            }
        }

        $notificationType = $errors > 0 ? 'warning' : 'success';
        $title = $errors > 0 ? 'Ekstrak Selesai dengan Error' : 'Ekstrak Berhasil';

        Notification::make()
            ->title($title)
            ->body($message)
            ->$notificationType()
            ->duration(10000)
            ->send();
    }
}
