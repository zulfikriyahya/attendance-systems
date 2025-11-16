<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instansi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function jabatan()
    {
        return $this->hasMany(Jabatan::class);
    }

    public function jadwalPresensis()
    {
        return $this->hasMany(JadwalPresensi::class);
    }
}
