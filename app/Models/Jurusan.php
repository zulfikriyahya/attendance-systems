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
 * @property string $instansi_id
 * @property string $nama
 * @property string|null $deskripsi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Instansi $instansi
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kelas> $kelas
 * @property-read int|null $kelas_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan whereInstansiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Jurusan withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Jurusan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
