<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JabatanResource\Pages\EditJabatan;
use App\Filament\Resources\JabatanResource\Pages\ListJabatans;
use App\Models\Instansi;
use App\Models\Jabatan;
use Filament\Forms\Components\CheckboxList;
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

class JabatanResource extends Resource
{
    protected static ?string $model = Jabatan::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Jabatan';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'jabatan';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Jabatan')
                    ->collapsible()
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 2,
                    ])
                    ->schema([
                        Select::make('instansi_id')
                            ->relationship('instansi', 'nama')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        TextInput::make('nama')
                            ->required()
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),

                        Textarea::make('deskripsi')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengaturan Jadwal Presensi')
                    ->collapsible()
                    ->schema([
                        CheckboxList::make('jadwalPresensis')
                            ->label('Jadwal Presensi')
                            ->relationship(
                                name: 'jadwalPresensis',
                                titleAttribute: 'nama',
                                modifyQueryUsing: fn ($query) => $query->where('status', true)->orderBy('nama', 'desc')
                            )
                            ->required()
                            ->columns(Instansi::first()->status === 'Negeri' ? 5 : 6)
                            ->validationMessages([
                                'required' => 'Form ini wajib diisi.',
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $searchable = Jabatan::count() > 10;

        return $table
            ->columns([
                ImageColumn::make('instansi.logoInstansi')
                    ->label('Logo Instansi')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('instansi.nama')
                    ->searchable($searchable),
                TextColumn::make('nama')
                    ->badge()
                    ->searchable($searchable),
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

    public static function getRelations(): array
    {
        return [
            // Relasi Resource Pegawai
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJabatans::route('/'),
            // 'create' => CreateJabatan::route('/create'),
            // 'view' => ViewJabatan::route('/{record}'),
            'edit' => EditJabatan::route('/{record}/edit'),
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
