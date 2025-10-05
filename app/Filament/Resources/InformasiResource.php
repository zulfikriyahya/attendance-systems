<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use App\Models\Informasi;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use App\Filament\Resources\InformasiResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                TextInput::make('judul')
                    ->label('Judul Informasi')
                    ->required(),
                DateTimePicker::make('tanggal')
                    ->label('Tanggal Informasi')
                    ->required()
                    ->displayFormat('l, d F Y H:i')
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
                Select::make('jabatan_id')
                    ->label('Kepada')
                    ->relationship('jabatan', 'nama')
                    ->native(false)
                    ->required(),
                FileUpload::make('lampiran')
                    ->label('Lampiran Informasi')
                    ->image()
                    ->maxFiles(1)
                    ->downloadable()
                    ->previewable()
                    ->directory('lampiranInformasi')
                    ->visibility('public')
                    ->maxSize(2048),
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('judul')
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
                TextColumn::make('isi')
                    ->label('Uraian')
                    ->limit(15)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 15) {
                            return null;
                        }

                        return $state;
                    }),
                TextColumn::make('tanggal')
                    ->dateTime('l, d F Y'),
                BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Publish' => 'success',
                        'Archive' => 'warning',
                        default => 'gray',
                    }),
                BadgeColumn::make('jabatan.nama')
                ->label('Kepada'),
                ImageColumn::make('lampiran'),
            ])
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
            'index' => Pages\ListInformasis::route('/'),
            'create' => Pages\CreateInformasi::route('/create'),
            'view' => Pages\ViewInformasi::route('/{record}'),
            'edit' => Pages\EditInformasi::route('/{record}/edit'),
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
            ->when($jabatanId, fn ($query) =>
                $query->where('jabatan_id', $jabatanId)
            )
            ->orderBy('tanggal', 'desc');
    }
}
