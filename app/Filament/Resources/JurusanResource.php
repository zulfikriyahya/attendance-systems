<?php

namespace App\Filament\Resources;

use App\Models\Jurusan;
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
use App\Filament\Resources\JurusanResource\Pages\EditJurusan;
use App\Filament\Resources\JurusanResource\Pages\ViewJurusan;
use App\Filament\Resources\JurusanResource\Pages\ListJurusans;
use App\Filament\Resources\JurusanResource\Pages\CreateJurusan;

class JurusanResource extends Resource
{
    protected static ?string $model = Jurusan::class;

    protected static bool $shouldRegisterNavigation = true; // true

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Jurusan';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'jurusan';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('instansi_id')
                    ->relationship('instansi', 'nama')
                    ->required(),
                TextInput::make('nama')
                    ->required(),
                Textarea::make('deskripsi')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('instansi.logoInstansi')
                    ->label('Logo Instansi')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('instansi.nama')
                    ->searchable(Jurusan::all()->count() > 10),
                TextColumn::make('nama')
                    ->badge()
                    ->searchable(Jurusan::all()->count() > 10),
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

    public static function getRelations(): array
    {
        return [
            // Relasi Resource Kelas
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJurusans::route('/'),
            'create' => CreateJurusan::route('/create'),
            'view' => ViewJurusan::route('/{record}'),
            'edit' => EditJurusan::route('/{record}/edit'),
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
