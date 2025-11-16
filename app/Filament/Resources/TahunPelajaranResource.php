<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TahunPelajaranResource\Pages\CreateTahunPelajaran;
use App\Filament\Resources\TahunPelajaranResource\Pages\EditTahunPelajaran;
use App\Filament\Resources\TahunPelajaranResource\Pages\ListTahunPelajarans;
use App\Filament\Resources\TahunPelajaranResource\Pages\ViewTahunPelajaran;
use App\Models\TahunPelajaran;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TahunPelajaranResource extends Resource
{
    protected static ?string $model = TahunPelajaran::class;

    protected static bool $shouldRegisterNavigation = true; // true

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Tahun Pelajaran';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'tahun-pelajaran';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tahun Pelajaran')
                    ->collapsible()
                    ->columns([
                        'sm' => 1,
                        'md' => 3,
                        'xl' => 5,
                    ])
                    ->schema([
                        Select::make('instansi_id')
                            ->relationship('instansi', 'nama')
                            ->required(),

                        TextInput::make('nama')
                            ->label('Tahun Pelajaran')
                            ->placeholder('Contoh: 2025/2026')
                            ->minLength(9)
                            ->maxLength(9)
                            ->unique(
                                ignorable: fn ($record) => $record,
                                table: TahunPelajaran::class,
                                column: 'nama',
                            )
                            ->required()
                            ->validationMessages([
                                'min' => 'Tahun Pelajaran tidak boleh kurang dari 9 karakter.',
                                'max' => 'Tahun Pelajaran tidak boleh lebih dari 9 karakter.',
                                'required' => 'Form ini harus diisi.',
                                'unique' => 'Tahun Pelajaran sudah ada.',
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

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('instansi.logoInstansi')
                    ->label('Logo Instansi')
                    ->disabledClick()
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('instansi.nama')
                    ->label('Nama Instansi')
                    ->disabledClick()
                    ->searchable(TahunPelajaran::all()->count() > 10),
                TextColumn::make('nama')
                    ->label('Tahun Pelajaran')
                    ->disabledClick()
                    ->badge()
                    ->searchable(TahunPelajaran::all()->count() > 10),
                TextColumn::make('mulai')
                    ->label('Tanggal Mulai')
                    ->disabledClick()
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('selesai')
                    ->label('Tanggal Selesai')
                    ->disabledClick()
                    ->date('d F Y')
                    ->sortable(),
                ToggleColumn::make('status')
                    ->label('Status')
                    ->disabledClick()
                    ->disabled(! Auth::user()->hasRole('super_admin'))
                    ->tooltip(fn ($state) => $state !== true ? 'Aktifkan!' : 'Nonaktifkan!'),
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
