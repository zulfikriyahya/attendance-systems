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
 * @property string $pegawai_id
 * @property \Illuminate\Support\Carbon|null $jamDatang
 * @property \Illuminate\Support\Carbon|null $jamPulang
 * @property \Illuminate\Support\Carbon|null $tanggal
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
 * @property-read string|null $pegawai_user_name
 * @property-read \App\Models\Pegawai $pegawai
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereBerkasLampiran($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereIsSynced($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereJamDatang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereJamPulang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai wherePegawaiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereStatusApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereStatusPresensi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereStatusPulang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereSyncMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PresensiPegawai withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PresensiPegawai extends Model
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
        return $this->pegawai?->user?->name ?? '-';
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function getPegawaiUserNameAttribute(): ?string
    {
        return $this->pegawai?->user?->name;
    }
}
