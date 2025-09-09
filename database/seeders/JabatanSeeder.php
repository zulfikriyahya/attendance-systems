<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Instansi;
use Illuminate\Support\Str;
use App\Models\JadwalPresensi;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        $instansi = Instansi::first();
        $jadwalAktif = JadwalPresensi::where('status', true)->get();

        if (! $instansi || $jadwalAktif->isEmpty()) {
            $this->command->error('Seeder gagal: Instansi atau jadwal presensi aktif belum tersedia.');

            return;
        }

        $jabatans = [
            ['nama' => 'Manajemen'],
            ['nama' => 'Staf'],
            ['nama' => 'Guru'],
            ['nama' => 'Siswa Unggulan'],
            ['nama' => 'Siswa Reguler'],
            ['nama' => 'Wali Kelas'],
        ];

        foreach ($jabatans as $data) {
            $jabatan = Jabatan::updateOrCreate(
                [
                    'id' => Str::uuid(),
                    'instansi_id' => $instansi->id,
                    'nama' => $data['nama'],
                    'deskripsi' => $data['nama'],
                ]
            );

            // Hubungkan ke semua jadwal aktif
            $jabatan->jadwalPresensis()->sync($jadwalAktif->pluck('id'));
        }

        $this->command->info('Seeder Jabatan berhasil dijalankan.');
    }
}
