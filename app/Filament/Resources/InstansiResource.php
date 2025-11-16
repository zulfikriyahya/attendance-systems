<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstansiResource\Pages\CreateInstansi;
use App\Filament\Resources\InstansiResource\Pages\EditInstansi;
use App\Filament\Resources\InstansiResource\Pages\ListInstansis;
use App\Filament\Resources\InstansiResource\Pages\ViewInstansi;
use App\Models\Instansi;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

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
                Section::make('Instansi')
                    ->collapsible()
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        FileUpload::make('logoInstansi')
                            // ->label('Logo Instansi')
                            ->avatar()
                            ->hiddenLabel()
                            ->alignCenter()
                            ->columnSpanFull()
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

                        // FileUpload::make('logoInstitusi')
                        //     ->label('Logo Institusi')
                        //     ->image()
                        //     ->imageEditor()
                        //     ->imageEditorAspectRatios([
                        //         '1:1',
                        //         '16:9',
                        //         '4:3',
                        //         null,
                        //     ])
                        //     ->maxSize(1024)
                        //     ->directory('logoInstitusi')
                        //     ->visibility('public'),

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
                    ]),

                Section::make('Detail')
                    ->collapsible()
                    ->columns([
                        'sm' => 1,
                        'md' => 3,
                        'xl' => 3,
                    ])
                    ->schema([
                        TextInput::make('website'),

                        TextInput::make('telepon')
                            ->tel()
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TextInput::make('email')
                            ->email(),

                        Textarea::make('alamat')
                            ->columnSpanFull()
                            ->maxLength(255)
                            ->minLength(5),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $searchable = Instansi::count() > 10;

        return $table
            ->columns([
                ImageColumn::make('logoInstitusi')
                    ->disabledClick()
                    ->label('Logo Institusi')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                ImageColumn::make('logoInstansi')
                    ->disabledClick()
                    ->label('Logo Instansi')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('nama')
                    ->disabledClick()
                    ->label('Nama Instansi')
                    ->searchable($searchable),
                TextColumn::make('nss')
                    ->disabledClick()
                    ->label('NSS')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('npsn')
                    ->disabledClick()
                    ->label('NPSN')
                    ->searchable($searchable)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('telepon')
                    ->disabledClick()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->disabledClick()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pimpinan')
                    ->disabledClick()
                    ->label('Nama Pimpinan'),
                ImageColumn::make('ttePimpinan')
                    ->disabledClick()
                    ->label('TTE Pimpinan')
                    ->defaultImageUrl('/images/default.png')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nipPimpinan')
                    ->disabledClick()
                    ->label('NIP Pimpinan'),
                TextColumn::make('akreditasi')
                    ->disabledClick()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'info',
                        'B' => 'success',
                        'C' => 'warning',
                        'D' => 'danger',
                    }),
                TextColumn::make('status')
                    ->disabledClick()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Negeri' => 'info',
                        'Swasta' => 'success',
                    }),
                TextColumn::make('website')
                    ->disabledClick()
                    ->toggleable(isToggledHiddenByDefault: true),
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
