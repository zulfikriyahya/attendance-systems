<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $judul
 * @property string $isi
 * @property string $tanggal
 * @property string $status
 * @property string $jabatan_id
 * @property string|null $lampiran
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Jabatan $jabatan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereIsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereJabatanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereJudul($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereLampiran($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Informasi withoutTrashed()
 * @mixin \Eloquent
 */
class Informasi extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
