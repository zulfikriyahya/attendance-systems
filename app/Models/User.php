<?php

namespace App\Models;

use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

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
}
