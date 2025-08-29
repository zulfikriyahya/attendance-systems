<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('username')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('password')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('avatar')
                ->rules(['max:255']),
            ImportColumn::make('status')
                ->boolean()
                ->rules(['boolean']),
            ImportColumn::make('roles')
                ->label('Peran')
                ->rules(['required', 'string']),
        ];
    }

    public function resolveRecord(): ?User
    {
        return User::firstOrNew([
            'username' => $this->data['username'],
        ]);

        return new User;
    }

    public function fillRecord(): void
    {
        $this->record->fill([
            'name' => $this->data['name'],
            'username' => $this->data['username'],
            'email' => $this->data['email'],
            'avatar' => $this->data['avatar'] ?? null,
            'status' => $this->data['status'] ?? true,
        ]);

        if (! $this->record->exists || (array_key_exists('password', $this->data) && $this->data['password'])) {
            $this->record->password = Hash::make($this->data['password']);
        }
    }

    public function afterSave(): void
    {
        if (array_key_exists('roles', $this->data) && $this->data['roles']) {
            $roles = collect(explode(',', $this->data['roles']))
                ->map(fn ($role) => trim($role))
                ->filter()
                ->map(function ($roleName) {
                    return Role::where('name', $roleName)->first();
                })
                ->filter()
                ->pluck('name')
                ->toArray();

            $this->record->syncRoles($roles);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your user import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
