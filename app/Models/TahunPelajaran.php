<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
