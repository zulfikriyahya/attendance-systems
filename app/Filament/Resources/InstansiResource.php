<?php

namespace App\Filament\Resources;

use App\Models\Instansi;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\DeleteAction;
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
use App\Filament\Resources\InstansiResource\Pages\EditInstansi;
use App\Filament\Resources\InstansiResource\Pages\ViewInstansi;
use App\Filament\Resources\InstansiResource\Pages\ListInstansis;
use App\Filament\Resources\InstansiResource\Pages\CreateInstansi;

class InstansiResource extends Resource
{
    protected static ?string $model = Instansi::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Instansi';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'instansi';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama')
                    ->label('Nama Instansi')
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                TextInput::make('nss')
                    ->label('NSS/NSM')
                    ->maxLength(12)
                    ->minLength(12)
                    ->unique(ignoreRecord: true)
                    ->numeric()
                    ->validationMessages([
                        'unique' => 'NSS/NSM sudah pernah diisi.',
                        'min_digits' => 'NSS/NSM harus 12 karakter',
                        'max_digits' => 'NSS/NSM harus 12 karakter',
                    ]),
                TextInput::make('npsn')
                    ->label('NPSN')
                    ->maxLength(8)
                    ->minLength(8)
                    ->unique(ignoreRecord: true)
                    ->numeric()
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                        'unique' => 'NPSN sudah pernah diisi.',
                        'min_digits' => 'NPSN harus 8 karakter',
                        'max_digits' => 'NPSN harus 8 karakter',
                    ]),
                FileUpload::make('logoInstansi')
                    ->label('Logo Instansi')
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ])
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '1:1',
                        '16:9',
                        '4:3',
                        null,
                    ])
                    ->maxSize(1024)
                    ->directory('logoInstansi')
                    ->visibility('public'),
                FileUpload::make('logoInstitusi')
                    ->label('Logo Institusi')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '1:1',
                        '16:9',
                        '4:3',
                        null,
                    ])
                    ->maxSize(1024)
                    ->directory('logoInstitusi')
                    ->visibility('public'),
                Textarea::make('alamat')
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->minLength(5),
                TextInput::make('telepon')
                    ->tel()
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                TextInput::make('email')
                    ->email(),
                TextInput::make('pimpinan')
                    ->label('Nama Pimpinan')
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                FileUpload::make('ttePimpinan')
                    ->label('TTE Pimpinan')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '1:1',
                        '16:9',
                        '4:3',
                        null,
                    ])
                    ->maxSize(1024)
                    ->directory('ttePimpinan')
                    ->visibility('public'),
                TextInput::make('nipPimpinan')
                    ->label('NIP Pimpinan')
                    // ->required(ignoreRecord: true)
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                Select::make('akreditasi')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'C' => 'C',
                        'D' => 'D',
                    ])
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                Select::make('status')
                    ->options([
                        'Negeri' => 'Negeri',
                        'Swasta' => 'Swasta',
                    ])
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                TextInput::make('website'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $searchable = Instansi::count() > 10;

        return $table
            ->columns([
                ImageColumn::make('logoInstitusi')
                    ->label('Logo Institusi')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                ImageColumn::make('logoInstansi')
                    ->label('Logo Instansi')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('nama')
                    ->label('Nama Instansi')
                    ->searchable($searchable),
                TextColumn::make('nss')
                    ->label('NSS')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('npsn')
                    ->label('NPSN')
                    ->searchable($searchable)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('telepon')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pimpinan')
                    ->label('Nama Pimpinan'),
                ImageColumn::make('ttePimpinan')
                    ->label('TTE Pimpinan')
                    ->defaultImageUrl('/images/default.png')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nipPimpinan')
                    ->label('NIP Pimpinan'),
                TextColumn::make('akreditasi')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'A' => 'info',
                        'B' => 'success',
                        'C' => 'warning',
                        'D' => 'danger',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Negeri' => 'info',
                        'Swasta' => 'success',
                    }),
                TextColumn::make('website')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ListInstansis::route('/'),
            'create' => CreateInstansi::route('/create'),
            'view' => ViewInstansi::route('/{record}'),
            'edit' => EditInstansi::route('/{record}/edit'),
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
