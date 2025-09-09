<?php

namespace Database\Seeders;

use App\Models\Instansi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InstansiSeeder extends Seeder
{
    public function run(): void
    {
        Instansi::create([
            'id' => Str::uuid(),
            'nama' => 'MTs Negeri 1 Pandeglang',
            'nss' => '121136010001',
            'npsn' => '69788409',
            'telepon' => '089612050291',
            'email' => 'admin@mtsn1pandeglang.sch.id',
            'pimpinan' => 'H. Eman Sulaiman, S.Ag., M.Pd.',
            'nipPimpinan' => '197006032000031002',
            'status' => 'Negeri',
            'akreditasi' => 'A',
            'website' => 'https://mtsn1pandeglang.sch.id',
        ]);
    }
}
