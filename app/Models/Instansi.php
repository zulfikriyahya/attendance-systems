<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $nama
 * @property string|null $nss
 * @property string|null $npsn
 * @property string|null $logoInstansi
 * @property string|null $logoInstitusi
 * @property string|null $alamat
 * @property string|null $telepon
 * @property string|null $email
 * @property string|null $pimpinan
 * @property string|null $ttePimpinan
 * @property string|null $nipPimpinan
 * @property string|null $akreditasi
 * @property string|null $status
 * @property string|null $website
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Jabatan> $jabatan
 * @property-read int|null $jabatan_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereAkreditasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereLogoInstansi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereLogoInstitusi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereNipPimpinan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereNpsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereNss($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi wherePimpinan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereTelepon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereTtePimpinan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instansi withoutTrashed()
 * @mixin \Eloquent
 */
class Instansi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            // 'nipPimpinan' => 'encrypted',
        ];
    }

    public function jabatan()
    {
        return $this->hasMany(Jabatan::class);
    }
}
