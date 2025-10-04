<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Informasi extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
