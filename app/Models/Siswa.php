<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    // Pivot

    public function semuaPresensi()
    {
        return $this->hasManyThrough(
            PresensiSiswa::class,
            KelasSiswaTahunPelajaran::class,
            'siswa_id',
            'kelas_siswa_tahun_pelajaran_id',
            'id',
            'id'
        );
    }

    public function kelasSiswaTahunPelajaran(): HasMany
    {
        return $this->hasMany(KelasSiswaTahunPelajaran::class);
    }

    public function kelasTahunPelajaran(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_siswa_tahun_pelajarans')
            ->withPivot('tahun_pelajaran_id')
            ->withTimestamps();
    }

    public function kelasSaatIni(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_siswa_tahun_pelajarans')
            ->withPivot('tahun_pelajaran_id')
            ->wherePivot('tahun_pelajaran_id', function ($query) {
                $query->select('id')
                    ->from('tahun_pelajarans')
                    ->where('status', true)
                    ->limit(1);
            });
    }

    public function tahunPelajaranSaatIni()
    {
        return $this->hasOneThrough(
            TahunPelajaran::class,
            KelasSiswaTahunPelajaran::class,
            'siswa_id',
            'id',
            'id',
            'tahun_pelajaran_id'
        )->where('status', true);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', true);
    }
}
