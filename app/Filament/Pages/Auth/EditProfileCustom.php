<?php

namespace App\Filament\Pages\Auth;

use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Auth\EditProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class EditProfileCustom extends EditProfile
{
    use HasCustomLayout;

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getAvatarFormComponent(),
                        $this->getNameFormComponent(),
                        $this->getUsernameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getTelephoneFormComponent(), // Tambahan field nomor telepon
                        $this->getAlamatFormComponent(), // Tambahan field alamat
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->getUser();

        // Ambil data telepon dan alamat dari relasi pegawai atau siswa
        if ($user->pegawai) {
            $data['telepon'] = $user->pegawai->telepon ?? '';
            $data['alamat'] = $user->pegawai->alamat ?? '';
        } elseif ($user->siswa) {
            $data['telepon'] = $user->siswa->telepon ?? '';
            $data['alamat'] = $user->siswa->alamat ?? '';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->getUser();

        // Simpan telepon dan alamat ke tabel relasi
        if ($user->pegawai && isset($data['telepon'])) {
            $user->pegawai->update([
                'telepon' => $data['telepon'],
                'alamat' => $data['alamat'] ?? null,
            ]);

            // Hapus dari data yang akan disave ke users table
            unset($data['telepon'], $data['alamat']);
        } elseif ($user->siswa && isset($data['telepon'])) {
            $user->siswa->update([
                'telepon' => $data['telepon'],
                'alamat' => $data['alamat'] ?? null,
            ]);

            // Hapus dari data yang akan disave ke users table
            unset($data['telepon'], $data['alamat']);
        }

        return $data;
    }

    protected function getAvatarFormComponent(): Component
    {
        return FileUpload::make('avatar')
            // ->label(__('Avatar'))
            ->hiddenLabel()
            ->avatar()
            ->image()
            ->directory('avatar')
            ->maxSize(1024)
            ->visibility('public')
            ->imageEditor()
            ->imageEditorAspectRatios([
                '1:1' => '1:1',
                null,
            ])
            ->circleCropper()
            ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                $username = $record?->username ?? 'user_'.time();
                $fileName = strtolower($username).'.png';

                // Buat manager dengan driver GD
                $manager = new ImageManager(new Driver);

                // Baca file & konversi ke PNG
                $image = $manager->read($file->getRealPath())->toPng();

                // Simpan manual ke storage/public/avatar
                Storage::disk('public')->put('avatar/'.$fileName, (string) $image);

                return $fileName;
            })
            ->extraAttributes([
                'class' => 'flex flex-col items-center',
            ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('Nama Lengkap'))
            ->suffixIcon('heroicon-o-user-circle')
            ->required()
            ->maxLength(100)
            ->autofocus()
            ->dehydrateStateUsing(function ($state) {
                // Pisahkan berdasarkan koma
                $parts = explode(',', $state, 2);

                // Kapital penuh sebelum koma
                $parts[0] = mb_strtoupper(trim($parts[0]));

                // Gabungkan kembali (jika ada koma)
                return isset($parts[1])
                    ? $parts[0].', '.trim($parts[1])
                    : $parts[0];
            });
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('NISN/NIP'))
            ->suffixIcon('heroicon-o-identification')
            // ->disabled();
            ->required()
            ->unique(ignoreRecord: true)
            ->rule(fn ($record) => $record === null ? 'unique:users,username' : 'unique:users,username,'.$record->id)
            ->dehydrateStateUsing(fn ($state) => $state ? $state : null)
            ->validationMessages([
                'unique' => 'Username tersebut sudah pernah di isi.',
                'required' => 'Form ini wajib diisi.',
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('Email'))
            ->suffixIcon('heroicon-o-envelope')
            ->email()
            ->required()
            ->maxLength(50)
            ->validationMessages([
                'max' => 'Email: Masukkan maksimal 50 Karakter.',
                'unique' => 'Email: Email ini sudah pernah di isi.',
                'required' => 'Form ini wajib diisi.',
            ])
            ->unique(ignoreRecord: true);
    }

    protected function getTelephoneFormComponent(): Component
    {
        $user = $this->getUser();

        // Cek apakah user adalah pegawai atau siswa
        if ($user->pegawai || $user->siswa) {
            return TextInput::make('telepon')
                ->label(__('Nomor Telepon'))
                ->suffixIcon('heroicon-o-phone')
                ->tel()
                ->required()
                ->maxLength(15)
                ->minLength(10)
                ->regex('/^[0-9+\-\s()]+$/')
                ->placeholder('Contoh: 08123456789 atau +6281234567890')
                ->helperText('Format: 08xx-xxxx-xxxx atau +62xxx-xxxx-xxxx')
                ->validationMessages([
                    'min' => 'Nomor telepon minimal 10 digit.',
                    'max' => 'Nomor telepon maksimal 15 karakter.',
                    'regex' => 'Format nomor telepon tidak valid. Gunakan angka, +, -, spasi, atau tanda kurung.',
                    'required' => 'Nomor telepon wajib diisi.',
                ]);
        } else {
            // Jika user bukan pegawai atau siswa, return hidden field
            return TextInput::make('telepon_placeholder')
                ->label(__('Nomor Telepon'))
                ->disabled()
                ->placeholder('Tidak tersedia untuk tipe akun ini')
                ->helperText('Akun Anda tidak memiliki data pegawai atau siswa')
                ->dehydrated(false);
        }
    }

    protected function getAlamatFormComponent(): Component
    {
        $user = $this->getUser();

        // Cek apakah user adalah pegawai atau siswa
        if ($user->pegawai || $user->siswa) {
            return Textarea::make('alamat')
                ->label(__('Alamat'))
                ->nullable()
                ->maxLength(500)
                ->rows(3)
                ->placeholder('Masukkan alamat lengkap Anda')
                ->helperText('Alamat domisili atau tempat tinggal saat ini')
                ->validationMessages([
                    'max' => 'Alamat maksimal 500 karakter.',
                ]);
        } else {
            // Jika user bukan pegawai atau siswa, return hidden field
            return Textarea::make('alamat_placeholder')
                ->label(__('Alamat'))
                ->disabled()
                ->placeholder('Tidak tersedia untuk tipe akun ini')
                ->helperText('Akun Anda tidak memiliki data pegawai atau siswa')
                ->rows(2)
                ->dehydrated(false);
        }
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('Password'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(Password::default())
            ->autocomplete('new-password')
            ->dehydrated(fn ($state): bool => filled($state))
            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation')
            ->validationMessages([
                'same' => 'Password: Password tidak sesuai dengan isian password konfirmasi.',
                'min' => 'Password: Masukkan minimal 8 karakter alfanumerik.',
                'required' => 'Form ini wajib diisi.',
            ]);
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('Ulangi Password'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->visible(fn (Get $get): bool => filled($get('password')))
            ->dehydrated(false);
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.pages.dashboard-admin');
    }
}
