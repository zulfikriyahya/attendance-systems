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
 * @property int $user_id
 * @property string $rfid
 * @property string $nip
 * @property string $jenisKelamin
 * @property string $telepon
 * @property string|null $alamat
 * @property string $jabatan_id
 * @property bool $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string|null $user_name
 * @property-read \App\Models\Jabatan $jabatan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KelasPegawai> $kelasPegawais
 * @property-read int|null $kelas_pegawais_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PresensiPegawai> $presensiPegawai
 * @property-read int|null $presensi_pegawai_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereJabatanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereJenisKelamin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereNip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereRfid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereTelepon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pegawai withoutTrashed()
 * @mixin \Eloquent
 */
class Pegawai extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            // 'nip' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUserNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function presensiPegawai(): HasMany
    {
        return $this->hasMany(PresensiPegawai::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function kelasPegawais(): HasMany
    {
        return $this->hasMany(KelasPegawai::class);
    }
}
