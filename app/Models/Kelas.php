<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $nama
 * @property string|null $deskripsi
 * @property string $jurusan_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Jurusan $jurusan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KelasTahunPelajaran> $kelasTahunPelajarans
 * @property-read int|null $kelas_tahun_pelajarans_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas whereJurusanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kelas withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Kelas extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function kelasTahunPelajarans(): HasMany
    {
        return $this->hasMany(KelasTahunPelajaran::class);
    }
}
