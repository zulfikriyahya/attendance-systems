<?php

namespace Database\Seeders;

use App\Models\Instansi;
use Illuminate\Support\Str;
use App\Models\JadwalPresensi;
use Illuminate\Database\Seeder;

class JadwalPresensiSeeder extends Seeder
{
    public function run(): void
    {
        $instansi = Instansi::first();

        if (! $instansi) {
            $this->command->error('Seeder gagal: Instansi belum ada.');

            return;
        }

        $data = [
            'Siswa Unggulan' => [
                'jamDatang' => '07:15:00',
                'jamPulang' => '16:20:00',
                'deskripsi' => 'Presensi Siswa Unggulan hari Senin - Kamis',
                'status' => true,
            ],
            'Siswa Reguler' => [
                'jamDatang' => '07:15:00',
                'jamPulang' => '15:20:00',
                'deskripsi' => 'Presensi Siswa Reguler hari Senin - Kamis',
                'status' => true,
            ],
            'Manajemen' => [
                'jamDatang' => '07:15:00',
                'jamPulang' => '15:45:00',
                'deskripsi' => 'Presensi Guru hari Senin - Kamis',
                'status' => true,
            ],
            'Guru' => [
                'jamDatang' => '07:15:00',
                'jamPulang' => '15:45:00',
                'deskripsi' => 'Presensi Guru hari Senin - Kamis',
                'status' => true,
            ],
            'Staf' => [
                'jamDatang' => '07:15:00',
                'jamPulang' => '15:45:00',
                'deskripsi' => 'Presensi Staf hari Senin - Kamis',
                'status' => true,
            ],
            'Ramadhan' => [
                'jamDatang' => [
                    'default' => '08:00:00',
                    'Jumat' => '08:00:00',
                ],
                'jamPulang' => [
                    'default' => '11:00:00',
                    'Jumat' => '14:00:00',
                ],
                'deskripsi' => 'Presensi Ramadhan hari Senin - Kamis',
                'status' => false,
            ],
        ];

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        foreach ($data as $jenis => $config) {
            foreach ($hariList as $hari) {
                JadwalPresensi::create([
                    'id' => Str::uuid(),
                    'instansi_id' => $instansi->id,
                    'nama' => "Jadwal {$jenis} {$hari}",
                    'deskripsi' => $config['deskripsi'] ?? null,
                    'hari' => $hari,
                    'jamDatang' => is_array($config['jamDatang']) ? ($config['jamDatang'][$hari] ?? $config['jamDatang']['default']) : $config['jamDatang'],
                    'jamPulang' => is_array($config['jamPulang']) ? ($config['jamPulang'][$hari] ?? $config['jamPulang']['default']) : $config['jamPulang'],
                    'status' => $config['status'],
                ]);
            }
        }
    }
}
