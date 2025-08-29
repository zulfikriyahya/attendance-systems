<?php

namespace App\Filament\Pages\Auth;

use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Register;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterCustom extends Register
{
    use HasCustomLayout;

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getUsernameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('Nama Lengkap'))
            ->required()
            ->suffixIcon('heroicon-o-user-circle')
            ->maxLength(100)
            ->autofocus();
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('Username'))
            ->required()
            ->suffixIcon('heroicon-o-identification')
            ->validationMessages([
                'unique' => 'Username ini sudah pernah di isi.',
                'required' => 'Form ini wajib diisi.',
            ])
            ->unique($this->getUserModel());
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('Email'))
            ->email()
            ->required()
            ->suffixIcon('heroicon-o-envelope')
            ->maxLength(50)
            ->validationMessages([
                'max' => 'Email: Masukkan maksimal 50 Karakter.',
                'unique' => 'Email: Email ini sudah pernah di isi.',
                'required' => 'Form ini wajib diisi.',
            ])
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('Password'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationMessages([
                'same' => 'Password: Password tidak sesuai dengan isian password konfirmasi.',
                'min' => 'Password: Masukkan minimal 8 karakter alfanumerik.',
                'required' => 'Form ini wajib diisi.',
            ])
            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('Ulangi Password'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }
}
