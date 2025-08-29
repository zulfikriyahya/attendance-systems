<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Filament\Imports\UserImporter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                TextInput::make('username')
                    ->required()
                    ->rule(fn($record) => $record === null ? 'unique:users,username' : 'unique:users,username,' . $record->id)
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                        'unique' => 'Username sudah ada.',
                    ]),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->rule(fn($record) => $record === null ? 'unique:users,email' : 'unique:users,email,' . $record->id)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                        'unique' => 'Username sudah ada.',
                    ]),
                DateTimePicker::make('email_verified_at')
                    ->label('Verifikasi Email')
                    ->default(now()),
                TextInput::make('password')
                    ->password()
                    ->required(fn($record) => $record === null)
                    ->dehydrateStateUsing(fn($state, $record) => $state ? bcrypt($state) : $record->password)
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                FileUpload::make('avatar')
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
                    ->visibility('public'),
                Select::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->getOptionLabelUsing(
                        fn($value): string => Str::title(str_replace('_', ' ', $value))
                    )
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
                Toggle::make('status')
                    ->default(true)
                    ->required()
                    ->validationMessages([
                        'required' => 'Form ini wajib diisi.',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $searchable = User::count() > 10;

        return $table
            ->headerActions([
                ImportAction::make('import')
                    ->label('Impor')
                    ->outlined()
                    ->color('primary')
                    ->importer(UserImporter::class)
                    ->visible(fn() => Auth::user()->hasRole('super_admin')),
            ])
            ->columns([
                ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl('/images/default.png'),
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable($searchable),
                TextColumn::make('username')
                    ->searchable($searchable),
                TextColumn::make('email')
                    ->searchable($searchable),
                TextColumn::make('email_verified_at')
                    ->label('Verifikasi Email')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('roles.name')
                    ->label('Peran')
                    ->formatStateUsing(
                        fn($state, $record) => $record->roles
                            ->pluck('name')
                            ->map(fn($name) => Str::title(str_replace('_', ' ', $name)))
                            ->join(', ')
                    )
                    ->searchable($searchable),
                IconColumn::make('status')
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
                    DeleteAction::make()
                        ->visible(
                            fn($record) => ! $record->roles->contains('name', 'super_admin')
                        ),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ], position: ActionsPosition::BeforeColumns)
            ->checkIfRecordIsSelectableUsing(
                fn($record) => ! $record->roles?->contains('name', 'super_admin')
            )
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
}
