<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $instansi_id
 * @property string $nama
 * @property string|null $deskripsi
 * @property string|null $hari
 * @property string|null $jamDatang
 * @property string|null $jamPulang
 * @property bool $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Instansi $instansi
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Jabatan> $jabatans
 * @property-read int|null $jabatans_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi aktif()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereHari($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereInstansiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereJamDatang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereJamPulang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalPresensi withoutTrashed()
 *
 * @mixin \Eloquent
 */
class JadwalPresensi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('status', true);
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }

    public function jabatans(): BelongsToMany
    {
        return $this->belongsToMany(Jabatan::class);
    }
}
