<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'manajemen']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'staf']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'guru']);
        $filamentUserRole = Role::firstOrCreate(['name' => 'siswa']);
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
