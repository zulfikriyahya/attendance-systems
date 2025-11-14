<?php

namespace Database\Seeders;

use App\Models\Instansi;
use App\Models\Jurusan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        $instansi = Instansi::first();

        if (! $instansi) {
            $this->command->error('Seeder gagal: Data instansi belum ada.');

            return;
        }

        $jurusanList = [
            ['nama' => 'Unggulan', 'deskripsi' => 'Jurusan Kelas Unggulan.'],
            ['nama' => 'Reguler', 'deskripsi' => 'Jurusan Kelas Reguler.'],
        ];

        foreach ($jurusanList as $jurusan) {
            Jurusan::create([
                'id' => Str::uuid(),
                'instansi_id' => $instansi->id,
                'nama' => $jurusan['nama'],
                'deskripsi' => $jurusan['deskripsi'],
            ]);
        }

        $this->command->info('Seeder Jurusan berhasil dijalankan.');
    }
}

// <?php

// namespace Database\Seeders;

// use App\Models\ProvinsiSeeder;
// use Illuminate\Database\Seeder;
// use Illuminate\Support\Str;

// class ProvinsiSeeder extends Seeder
// {
//     public function run(): void
//     {
//         Provinsi::create(
//             [
//                 'id' => Str::uuid(),
//                 'kode'=> '',
//                 'nama'=> '',
//             ],
//     );
//     }
// }
