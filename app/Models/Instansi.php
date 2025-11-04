<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function jadwalPresensis()
    {
        return $this->hasMany(JadwalPresensi::class);
    }
}
