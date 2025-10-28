<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $instansi_id
 * @property string $nama
 * @property string|null $deskripsi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Informasi> $informasis
 * @property-read int|null $informasis_count
 * @property-read \App\Models\Instansi $instansi
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JadwalPresensi> $jadwalPresensis
 * @property-read int|null $jadwal_presensis_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JadwalPresensi> $jadwalPresensisAktif
 * @property-read int|null $jadwal_presensis_aktif_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pegawai> $pegawai
 * @property-read int|null $pegawai_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Siswa> $siswa
 * @property-read int|null $siswa_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan whereInstansiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jabatan withoutTrashed()
 * @mixin \Eloquent
 */
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
