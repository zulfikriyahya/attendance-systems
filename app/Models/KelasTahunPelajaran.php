<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $kelas_id
 * @property string $tahun_pelajaran_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Kelas $kelas
 * @property-read \App\Models\TahunPelajaran $tahunPelajaran
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran whereKelasId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran whereTahunPelajaranId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasTahunPelajaran withoutTrashed()
 * @mixin \Eloquent
 */
class KelasTahunPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(TahunPelajaran::class);
    }
}
