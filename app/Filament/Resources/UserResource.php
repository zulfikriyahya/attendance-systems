<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('super_admin')) {
            return null; // hanya admin yang bisa lihat badge
        }

        return static::getModel()::where('status', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Pengguna')
                    ->collapsible()
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TextInput::make('username')
                            ->required()
                            ->rule(fn ($record) => $record === null ? 'unique:users,username' : 'unique:users,username,'.$record->id)
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                                'unique' => 'Username sudah ada.',
                            ]),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->rule(fn ($record) => $record === null ? 'unique:users,email' : 'unique:users,email,'.$record->id)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                                'unique' => 'Username sudah ada.',
                            ]),

                        TextInput::make('password')
                            ->password()
                            ->required(fn ($record) => $record === null)
                            ->dehydrateStateUsing(fn ($state, $record) => $state ? bcrypt($state) : $record->password)
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                    ]),

                Section::make('Detail')
                    ->collapsible()
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        FileUpload::make('avatar')
                            ->hiddenLabel()
                            ->alignCenter()
                            ->columnSpanFull()
                            ->avatar()
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1' => '1:1',
                                '3:4' => '3:4',
                                '4:3' => '4:3',
                                null,
                            ])
                            ->circleCropper()
                            ->maxSize(1024)
                            ->directory('avatar')
                            ->visibility('public')
                            ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                                $username = $record?->username ?? 'user_'.time();
                                $fileName = strtolower($username).'.png';
                                $manager = new ImageManager(new Driver);
                                $image = $manager->read($file->getRealPath());
                                Storage::disk('public')->put('avatar/'.$fileName, (string) $image->toPng());

                                return $fileName;
                            }),

                        DateTimePicker::make('email_verified_at')
                            ->label('Verifikasi Email')
                            ->default(now()),

                        Select::make('roles')
                            ->label('Peran')
                            ->relationship('roles', 'name')
                            ->required()
                            ->preload()
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $searchable = User::count() > 10;

        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->disabledClick()
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('name')
                    ->disabledClick()
                    ->label('Nama Lengkap')
                    ->searchable($searchable),
                TextColumn::make('username')
                    ->disabledClick()
                    ->searchable($searchable),
                TextColumn::make('email')
                    ->disabledClick()
                    ->searchable($searchable),
                TextColumn::make('email_verified_at')
                    ->disabledClick()
                    ->label('Verifikasi Email')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(Auth::user()->hasRole('super_admin') && User::all()->count() > 10),
                TextColumn::make('roles.name')
                    ->disabledClick()
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state, $record) => $record->roles
                            ->pluck('name')
                            ->map(fn ($name) => Str::title(str_replace('_', ' ', $name)))
                            ->join(', ')
                    )
                    ->searchable($searchable),
                ToggleColumn::make('status')
                    ->disabledClick()
                    ->label('Status'),
            ])
            ->paginationPageOptions([5, 10, 25])
            ->filters([
                TrashedFilter::make()
                    ->label('Trashed')
                    ->native(false),
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
                        ->icon('heroicon-o-minus-circle')
                        ->visible(
                            fn ($record) => ! $record->roles->contains('name', 'super_admin')
                        ),
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
            ->checkIfRecordIsSelectableUsing(
                fn ($record) => ! $record->roles?->contains('name', 'super_admin')
            )
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function extractZipToStorage(array $data): void
    {
        try {
            $disk = Storage::disk('public');
            $relativePath = $data['zip_file'];
            $zipFilePath = $disk->path($relativePath);

            $extractFolder = 'avatar';
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
