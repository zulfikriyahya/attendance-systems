<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\HasDatabaseNotifications;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    use HasDatabaseNotifications, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasVerifiedEmail() && $this->status == true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar
            ? asset('storage/'.($this->avatar))
            : null;
    }

    public function pegawai(): HasOne
    {
        return $this->hasOne(Pegawai::class);
    }

    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class);
    }

    public function pengajuanKartu(): HasOne
    {
        return $this->hasOne(PengajuanKartu::class);
    }

    /**
     * Get phone number from siswa or pegawai
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->siswa?->telepon ?? $this->pegawai?->telepon;
    }

    /**
     * Check if user is siswa
     */
    public function isSiswa(): bool
    {
        return $this->siswa()->exists();
    }

    /**
     * Check if user is pegawai
     */
    public function isPegawai(): bool
    {
        return $this->pegawai()->exists();
    }

    /**
     * Get user's instansi name
     */
    public function getInstansiNameAttribute(): string
    {
        return $this->siswa?->jabatan?->instansi?->nama
            ?? $this->pegawai?->jabatan?->instansi?->nama
            ?? 'Instansi';
    }
}
