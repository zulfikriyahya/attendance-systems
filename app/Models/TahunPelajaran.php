<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TahunPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'mulai' => 'date',
            'selesai' => 'date',
        ];
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }

    // Pivot

    public function kelasSiswaTahunPelajaran(): HasMany
    {
        return $this->hasMany(KelasSiswaTahunPelajaran::class);
    }

    public function kelasSiswa()
    {
        return $this->hasManyThrough(
            Siswa::class,
            KelasSiswaTahunPelajaran::class,
            'tahun_pelajaran_id',
            'id',
            'id',
            'siswa_id'
        );
    }

    public function semuaKelas()
    {
        return $this->hasManyThrough(
            Kelas::class,
            KelasSiswaTahunPelajaran::class,
            'tahun_pelajaran_id',
            'id',
            'id',
            'kelas_id'
        )->distinct();
    }

    public function scopeAktif($query)
    {
        return $query->where('status', true);
    }
}
