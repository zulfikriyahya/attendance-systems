<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public Collection $permissions;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('View')
                ->color(Color::Zinc)
                ->size('sm')
                ->icon('heroicon-o-eye')
                ->outlined(),
            DeleteAction::make()
                ->label('Delete')
                ->color(Color::Red)
                ->size('sm')
                ->icon('heroicon-o-minus-circle')
                ->outlined(),
            ForceDeleteAction::make()
                ->label('Force Delete')
                ->color(Color::Red)
                ->size('sm')
                ->icon('heroicon-o-trash')
                ->outlined(),
            RestoreAction::make()
                ->label('Restore')
                ->color(Color::Blue)
                ->size('sm')
                ->icon('heroicon-o-arrow-path')
                ->outlined(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(function ($permission, $key) {
                return ! in_array($key, ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()]);
            })
            ->values()
            ->flatten()
            ->unique();

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterSave(): void
    {
        $permissionModels = collect();
        $this->permissions->each(function ($permission) use ($permissionModels) {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->data['guard_name'],
            ]));
        });

        $this->record->syncPermissions($permissionModels);
    }
}
