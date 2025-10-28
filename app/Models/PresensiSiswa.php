<?php

namespace App\Models;

use App\Enums\StatusApproval;
use App\Enums\StatusPresensi;
use App\Enums\StatusPulang;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $siswa_id
 * @property \Illuminate\Support\Carbon|null $tanggal
 * @property \Illuminate\Support\Carbon|null $jamDatang
 * @property \Illuminate\Support\Carbon|null $jamPulang
 * @property StatusPresensi|null $statusPresensi
 * @property StatusPulang|null $statusPulang
 * @property bool $is_synced
 * @property \Illuminate\Support\Carbon|null $synced_at
 * @property string|null $device_id
 * @property array<array-key, mixed>|null $sync_metadata
 * @property string|null $catatan
 * @property StatusApproval|null $statusApproval
 * @property string|null $berkasLampiran
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $nama_lengkap
 * @property-read string|null $siswa_user_name
 * @property-read \App\Models\Siswa $siswa
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereBerkasLampiran($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereIsSynced($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereJamDatang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereJamPulang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereSiswaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereStatusApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereStatusPresensi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereStatusPulang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereSyncMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiSiswa withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PresensiSiswa extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jamDatang' => 'datetime:H:i:s',
            'jamPulang' => 'datetime:H:i:s',
            'statusPresensi' => StatusPresensi::class,
            'statusPulang' => StatusPulang::class,
            'statusApproval' => StatusApproval::class,
            'is_synced' => 'boolean',
            'synced_at' => 'datetime',
            'sync_metadata' => 'array',
        ];
    }

    public function getNamaLengkapAttribute(): string
    {
        return $this->siswa?->user?->name ?? '-';
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function getSiswaUserNameAttribute(): ?string
    {
        return $this->siswa?->user?->name;
    }
}
