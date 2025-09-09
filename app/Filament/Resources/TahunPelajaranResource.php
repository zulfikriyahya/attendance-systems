<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TahunPelajaranResource\Pages\CreateTahunPelajaran;
use App\Filament\Resources\TahunPelajaranResource\Pages\EditTahunPelajaran;
use App\Filament\Resources\TahunPelajaranResource\Pages\ListTahunPelajarans;
use App\Filament\Resources\TahunPelajaranResource\Pages\ViewTahunPelajaran;
use App\Models\TahunPelajaran;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TahunPelajaranResource extends Resource
{
    protected static ?string $model = TahunPelajaran::class;

    protected static bool $shouldRegisterNavigation = true; // true

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Tahun Pelajaran';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'tahun-pelajaran';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('instansi_id')
                    ->relationship('instansi', 'nama')
                    ->required(),
                TextInput::make('nama')
                    ->label('Tahun Pelajaran')
                    ->placeholder('2025/2026')
                    ->minLength(9)
                    ->maxLength(9)
                    ->required()
                    ->validationMessages([
                        'min' => 'Tahun Pelajaran tidak boleh kurang dari 9 karakter.',
                        'max' => 'Tahun Pelajaran tidak boleh lebih dari 9 karakter.',
                        'required' => 'Form ini harus diisi.',
                    ]),

                DatePicker::make('mulai')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini harus diisi.',
                    ])
                    ->default(Carbon::create(now()->year, 7, 1)),
                DatePicker::make('selesai')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini harus diisi.',
                    ])
                    ->default(Carbon::create(now()->year + 1, 6, 30)),
                Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Toggle::make('status')
                    ->label('Status')
                    ->default(true)
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini harus diisi.',
                    ])
                    ->afterStateUpdated(function ($state, $record) {
                        if ($state) {
                            TahunPelajaran::where('id', '!=', optional($record)->id)
                                ->where('status', true)
                                ->update(['status' => false]);
                        }
                    }),

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
                    ->label('Nama Instansi')
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Tahun Pelajaran')
                    ->badge()
                    ->searchable(),
                TextColumn::make('mulai')
                    ->label('Tanggal Mulai')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('selesai')
                    ->label('Tanggal Selesai')
                    ->date('d F Y')
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Status')
                    ->boolean(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTahunPelajarans::route('/'),
            'create' => CreateTahunPelajaran::route('/create'),
            'view' => ViewTahunPelajaran::route('/{record}'),
            'edit' => EditTahunPelajaran::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->orderBy('mulai', 'desc');
    }
}
