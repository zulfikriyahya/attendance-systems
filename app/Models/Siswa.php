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
 * @property string $nisn
 * @property string $jabatan_id
 * @property string $jenisKelamin
 * @property string $telepon
 * @property string|null $alamat
 * @property bool $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string|null $user_name
 * @property-read \App\Models\Jabatan $jabatan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KelasSiswa> $kelasSiswas
 * @property-read int|null $kelas_siswas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KelasTahunPelajaran> $kelasTahunPelajaran
 * @property-read int|null $kelas_tahun_pelajaran_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PresensiSiswa> $presensiSiswa
 * @property-read int|null $presensi_siswa_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa aktif()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereJabatanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereJenisKelamin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereNisn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereRfid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereTelepon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Siswa withoutTrashed()
 * @mixin \Eloquent
 */
class Siswa extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            // 'nisn' => 'encrypted',
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

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function presensiSiswa(): HasMany
    {
        return $this->hasMany(PresensiSiswa::class);
    }

    public function kelasSiswas(): HasMany
    {
        return $this->hasMany(KelasSiswa::class);
    }

    public function kelasTahunPelajaran()
    {
        return $this->hasManyThrough(KelasTahunPelajaran::class, KelasSiswa::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', true);
    }
}
