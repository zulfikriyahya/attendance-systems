<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jabatan extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    public function informasis(): HasMany
    {
        return $this->hasMany(Informasi::class);
    }

    public function jadwalPresensis(): BelongsToMany
    {
        return $this->belongsToMany(JadwalPresensi::class);
    }

    public function jadwalPresensisAktif(): BelongsToMany
    {
        return $this->belongsToMany(JadwalPresensi::class)
            ->where('status', true);
    }
}
