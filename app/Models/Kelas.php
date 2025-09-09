<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function kelasSiswaTahunPelajaran(): HasMany
    {
        return $this->hasMany(KelasSiswaTahunPelajaran::class);
    }

    public function siswaMelaluiPivot(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'kelas_siswa_tahun_pelajarans')
            ->withPivot('tahun_pelajaran_id')
            ->withTimestamps();
    }

    public function siswaSaatIni(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'kelas_siswa_tahun_pelajarans')
            ->withPivot('tahun_pelajaran_id')
            ->wherePivot('tahun_pelajaran_id', function ($query) {
                $query->select('id')
                    ->from('tahun_pelajarans')
                    ->where('status', true)
                    ->limit(1);
            });
    }

    public function getJumlahSiswaSaatIniAttribute(): int
    {
        return $this->siswaSaatIni()->count();
    }
}
