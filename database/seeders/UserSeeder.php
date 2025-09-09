<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'manajemen']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'staf']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'guru']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'siswa_unggulan']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'siswa_reguler']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'wali_kelas']);

        $users = [
            [
                'name' => 'Administrator',
                'username' => 'administrator',
                'email' => 'admin@mtsn1pandeglang.sch.id',
                'password' => 'P@ssw0rd',
                'avatar' => 'avatar/administrator.png',
                'role' => ['super_admin'],
            ],
            [
                'name' => 'Staf',
                'username' => 'staf',
                'email' => 'staf@mtsn1pandeglang.sch.id',
                'password' => 'P@ssw0rd',
                'avatar' => 'avatar/staf.png',
                'role' => ['staf'],
            ],
            [
                'name' => 'Guru',
                'username' => 'guru',
                'email' => 'guru@mtsn1pandeglang.sch.id',
                'password' => 'P@ssw0rd',
                'avatar' => 'avatar/guru.png',
                'role' => ['guru'],
            ],
            [
                'name' => 'Wali Kelas',
                'username' => 'walikelas',
                'email' => 'walikelas@mtsn1pandeglang.sch.id',
                'password' => 'P@ssw0rd',
                'avatar' => 'avatar/walikelas.png',
                'role' => ['wali_kelas'],
            ],
            [
                'name' => 'Siswa Unggulan',
                'username' => 'siswaunggulan',
                'email' => 'siswaunggulan@mtsn1pandeglang.sch.id',
                'password' => 'P@ssw0rd',
                'avatar' => 'avatar/siswaunggulan.png',
                'role' => ['siswa_unggulan'],
            ],
            [
                'name' => 'Siswa Reguler',
                'username' => 'siswareguler',
                'email' => 'siswareguler@mtsn1pandeglang.sch.id',
                'password' => 'P@ssw0rd',
                'avatar' => 'avatar/siswareguler.png',
                'role' => ['siswa_reguler'],
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['username' => $userData['username']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'avatar' => $userData['avatar'],
                    'status' => $userData['status'] ?? true,
                ]
            );

            // Set role-nya
            if (isset($userData['role'])) {
                $user->syncRoles($userData['role']);
            }
        }
    }
}
