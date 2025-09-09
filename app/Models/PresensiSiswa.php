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
}
