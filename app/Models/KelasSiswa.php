<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $kelas_tahun_pelajaran_id
 * @property string $siswa_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\KelasTahunPelajaran $kelasTahunPelajaran
 * @property-read \App\Models\Siswa $siswa
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa whereKelasTahunPelajaranId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa whereSiswaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasSiswa withoutTrashed()
 *
 * @mixin \Eloquent
 */
class KelasSiswa extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function kelasTahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(KelasTahunPelajaran::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
