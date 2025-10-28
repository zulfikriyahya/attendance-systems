<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $mata_pelajaran_id
 * @property string $pegawai_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\MataPelajaran $mataPelajaran
 * @property-read \App\Models\Pegawai $pegawai
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran whereMataPelajaranId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran wherePegawaiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuruMataPelajaran withoutTrashed()
 *
 * @mixin \Eloquent
 */
class GuruMataPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
