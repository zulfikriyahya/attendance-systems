<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $instansi_id
 * @property string $nama
 * @property \Illuminate\Support\Carbon|null $mulai
 * @property \Illuminate\Support\Carbon|null $selesai
 * @property string|null $deskripsi
 * @property bool $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Instansi $instansi
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KelasTahunPelajaran> $kelasTahunPelajarans
 * @property-read int|null $kelas_tahun_pelajarans_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereInstansiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereMulai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereSelesai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TahunPelajaran withoutTrashed()
 *
 * @mixin \Eloquent
 */
class TahunPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'mulai' => 'date',
            'selesai' => 'date',
        ];
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }

    public function kelasTahunPelajarans(): HasMany
    {
        return $this->hasMany(KelasTahunPelajaran::class);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            // Jika akan diaktifkan
            if ($model->isDirty('status') && $model->status === true) {
                static::where('status', true)
                    ->where('id', '!=', $model->id)
                    ->update(['status' => false]);
            }

            // Jika akan dinonaktifkan dan ini satu-satunya yang aktif
            if ($model->isDirty('status') && $model->status === false) {
                $jumlahAktif = static::where('status', true)->count();

                if ($jumlahAktif === 1) {
                    Notification::make()
                        ->title('Gagal Menonaktifkan')
                        ->body('Minimal satu tahun pelajaran harus tetap aktif.')
                        ->danger()
                        ->send();

                    // Batalkan penyimpanan
                    return false;
                }
            }
        });
    }
}
