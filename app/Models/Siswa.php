<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            // 'nisn' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUserNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function presensiSiswa(): HasMany
    {
        return $this->hasMany(PresensiSiswa::class);
    }

    public function kelasSiswas(): HasMany
    {
        return $this->hasMany(KelasSiswa::class);
    }

    public function kelasTahunPelajaran()
    {
        return $this->hasManyThrough(KelasTahunPelajaran::class, KelasSiswa::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', true);
    }
}
