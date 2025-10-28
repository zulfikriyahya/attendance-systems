<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $kelas_tahun_pelajaran_id
 * @property string $pegawai_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\KelasTahunPelajaran $kelasTahunPelajaran
 * @property-read \App\Models\Pegawai $pegawai
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai whereKelasTahunPelajaranId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai wherePegawaiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KelasPegawai withoutTrashed()
 *
 * @mixin \Eloquent
 */
class KelasPegawai extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function kelasTahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(KelasTahunPelajaran::class);
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }
}
