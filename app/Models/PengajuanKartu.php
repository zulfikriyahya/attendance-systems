<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $user_id
 * @property string $nomorPengajuanKartu
 * @property \Illuminate\Support\Carbon $tanggalPengajuanKartu
 * @property string $alasanPengajuanKartu
 * @property string $status
 * @property int|null $biaya
 * @property bool $statusAmbil
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-write mixed $tanggal_pengajuan_kartu
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereAlasanPengajuanKartu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereBiaya($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereNomorPengajuanKartu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereStatusAmbil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereTanggalPengajuanKartu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanKartu withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PengajuanKartu extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'tanggalPengajuanKartu' => 'datetime',
        'user_id' => 'integer',
        'biaya' => 'integer',
        'statusAmbil' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Mutator untuk memastikan format yang benar
    public function setTanggalPengajuanKartuAttribute($value)
    {
        $this->attributes['tanggalPengajuanKartu'] = $value instanceof Carbon
            ? $value->format('Y-m-d H:i:s')
            : Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
