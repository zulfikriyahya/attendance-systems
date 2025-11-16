<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InformasiResource\Pages\CreateInformasi;
use App\Filament\Resources\InformasiResource\Pages\EditInformasi;
use App\Filament\Resources\InformasiResource\Pages\ListInformasis;
use App\Filament\Resources\InformasiResource\Pages\ViewInformasi;
use App\Models\Informasi;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
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

class InformasiResource extends Resource
{
    protected static ?string $model = Informasi::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Informasi';

    protected static ?string $recordTitleAttribute = 'judul';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'informasi';

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

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
                        Section::make('Informasi')
                            ->collapsible()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 7,
                            ])
                            ->columns(2)
                            ->schema([
                                TextInput::make('judul')
                                    ->label('Judul Informasi')
                                    ->required(),

                                Select::make('jabatan_id')
                                    ->label('Kepada')
                                    ->relationship('jabatan', 'nama')
                                    ->native(false)
                                    ->required(),

                                MarkdownEditor::make('isi')
                                    ->label('Uraian Informasi')
                                    ->required()
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'strike',
                                        'bulletList',
                                        'orderedList',
                                    ]),
                            ]),

                        Section::make('Detail')
                            ->collapsible()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 5,
                            ])
                            ->columns(2)
                            ->schema([
                                DateTimePicker::make('tanggal')
                                    ->label('Tanggal Informasi')
                                    ->required()
                                    ->displayFormat('l, d F Y')
                                    ->native(false)
                                    ->default(now())
                                    ->maxDate(now()),

                                Select::make('status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Publish' => 'Publish',
                                        'Archive' => 'Archive',
                                    ])
                                    ->default('Publish')
                                    ->native(false)
                                    ->required(),

                                FileUpload::make('lampiran')
                                    ->label('Lampiran Informasi')
                                    ->image()
                                    ->maxFiles(1)
                                    ->columnSpanFull()
                                    ->downloadable()
                                    ->openable()
                                    ->directory('lampiranInformasi')
                                    ->visibility('public')
                                    ->maxSize(2048),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jabatan.nama')
                    ->disabledClick()
                    ->badge()
                    ->label('Kepada'),
                TextColumn::make('judul')
                    ->disabledClick()
                    ->label('Judul')
                    ->searchable(Informasi::all()->count() > 10)
                    ->limit(15)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 15) {
                            return null;
                        }

                        return $state;
                    })
                    ->weight(FontWeight::Medium),
                TextColumn::make('tanggal')
                    ->disabledClick()
                    ->label('Tanggal')
                    ->dateTime('l, d F Y'),
                TextColumn::make('isi')
                    ->disabledClick()
                    ->label('Uraian')
                    ->limit(15)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 15) {
                            return null;
                        }

                        return $state;
                    }),
                ImageColumn::make('lampiran')
                    ->disabledClick()
                    ->label('Lampiran'),
                TextColumn::make('status')
                    ->disabledClick()
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => 'Draft',
                        'success' => 'Publish',
                        'warning' => 'Archive',
                    ]),
            ])
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInformasis::route('/'),
            'create' => CreateInformasi::route('/create'),
            'view' => ViewInformasi::route('/{record}'),
            'edit' => EditInformasi::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Kalau super_admin => tampilkan semua
        if ($user->hasRole('super_admin')) {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->orderBy('tanggal', 'desc');
        }

        // Cari jabatan user (entah dari pegawai atau siswa)
        $jabatanId = null;

        if ($user->pegawai) {
            $jabatanId = $user->pegawai?->jabatan_id;
        } elseif ($user->siswa) {
            $jabatanId = $user->siswa?->jabatan_id;
        }

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('status', 'Publish') // hanya publish
            ->when(
                $jabatanId,
                fn ($query) => $query->where('jabatan_id', $jabatanId)
            )
            ->orderBy('tanggal', 'desc');
    }
}
