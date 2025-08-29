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
            ['nama' => 'Unggulan', 'deskripsi' => 'Kelas Unggulan'],
            ['nama' => 'Reguler', 'deskripsi' => 'Kelas Reguler'],
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
