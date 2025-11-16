<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
