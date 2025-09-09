<?php

namespace App\Filament\Pages\Auth;

use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class LoginCustom extends Login
{
    use HasCustomLayout;

    public function getTitle(): string|Htmlable
    {
        return 'Masuk ke Sistem Presensi';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Selamat Datang Kembali';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Silakan masuk dengan akun Anda untuk melanjutkan';
    }

    protected function getLayoutData(): array
    {
        return [
            'emptyPanelBackgroundImageUrl' => $this->getBackgroundImage(),
            'emptyPanelBackgroundColor' => $this->getBackgroundColor(),
        ];
    }

    protected function getBackgroundImage(): string
    {
        // Bisa mengembalikan gambar berbeda untuk light/dark mode
        return asset('/images/wallpaper.png');
    }

    protected function getBackgroundColor(): string
    {
        // Return empty string untuk menggunakan CSS theme
        return '';
    }

    // Custom CSS untuk halaman login
    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'customStyles' => '
                <style>
                    .fi-simple-layout {
                        min-height: 100vh;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    }
                    
                    .dark .fi-simple-layout {
                        background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
                    }
                    
                    @media (prefers-color-scheme: light) {
                        .fi-simple-layout {
                            background: linear-gradient(135deg, #f0f2f5 0%, #ffffff 100%);
                        }
                    }
                </style>
            ',
        ]);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getLoginFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label(__('NIK/NIP/NISN'))
            ->required()
            ->suffixIcon('heroicon-o-lock-closed')
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login_type = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $login_type => $data['login'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
