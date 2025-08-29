<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":[]},{"name":"admin_sekolah_1","guard_name":"web","permissions":["view_instansi","view_any_instansi","create_instansi","update_instansi","restore_instansi","restore_any_instansi","delete_instansi","delete_any_instansi","force_delete_instansi","force_delete_any_instansi","view_jabatan","view_any_jabatan","create_jabatan","update_jabatan","restore_jabatan","restore_any_jabatan","delete_jabatan","delete_any_jabatan","force_delete_jabatan","force_delete_any_jabatan","view_jadwal::presensi","view_any_jadwal::presensi","create_jadwal::presensi","update_jadwal::presensi","restore_jadwal::presensi","restore_any_jadwal::presensi","delete_jadwal::presensi","delete_any_jadwal::presensi","force_delete_jadwal::presensi","force_delete_any_jadwal::presensi","view_jurusan","view_any_jurusan","create_jurusan","update_jurusan","restore_jurusan","restore_any_jurusan","delete_jurusan","delete_any_jurusan","force_delete_jurusan","force_delete_any_jurusan","view_kelas","view_any_kelas","create_kelas","update_kelas","restore_kelas","restore_any_kelas","delete_kelas","delete_any_kelas","force_delete_kelas","force_delete_any_kelas","view_pegawai","view_any_pegawai","create_pegawai","update_pegawai","restore_pegawai","restore_any_pegawai","delete_pegawai","delete_any_pegawai","force_delete_pegawai","force_delete_any_pegawai","view_presensi::pegawai","view_any_presensi::pegawai","create_presensi::pegawai","update_presensi::pegawai","restore_presensi::pegawai","restore_any_presensi::pegawai","delete_presensi::pegawai","delete_any_presensi::pegawai","force_delete_presensi::pegawai","force_delete_any_presensi::pegawai","view_presensi::siswa","view_any_presensi::siswa","create_presensi::siswa","update_presensi::siswa","restore_presensi::siswa","restore_any_presensi::siswa","delete_presensi::siswa","delete_any_presensi::siswa","force_delete_presensi::siswa","force_delete_any_presensi::siswa","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_siswa","view_any_siswa","create_siswa","update_siswa","restore_siswa","restore_any_siswa","delete_siswa","delete_any_siswa","force_delete_siswa","force_delete_any_siswa","view_tahun::pelajaran","view_any_tahun::pelajaran","create_tahun::pelajaran","update_tahun::pelajaran","restore_tahun::pelajaran","restore_any_tahun::pelajaran","delete_tahun::pelajaran","delete_any_tahun::pelajaran","force_delete_tahun::pelajaran","force_delete_any_tahun::pelajaran","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","widget_PresensiPegawai"]},{"name":"admin_sekolah_2","guard_name":"web","permissions":["view_instansi","view_any_instansi","create_instansi","update_instansi","restore_instansi","restore_any_instansi","delete_instansi","delete_any_instansi","force_delete_instansi","force_delete_any_instansi","view_jabatan","view_any_jabatan","create_jabatan","update_jabatan","restore_jabatan","restore_any_jabatan","delete_jabatan","delete_any_jabatan","force_delete_jabatan","force_delete_any_jabatan","view_jadwal::presensi","view_any_jadwal::presensi","create_jadwal::presensi","update_jadwal::presensi","restore_jadwal::presensi","restore_any_jadwal::presensi","delete_jadwal::presensi","delete_any_jadwal::presensi","force_delete_jadwal::presensi","force_delete_any_jadwal::presensi","view_jurusan","view_any_jurusan","create_jurusan","update_jurusan","restore_jurusan","restore_any_jurusan","delete_jurusan","delete_any_jurusan","force_delete_jurusan","force_delete_any_jurusan","view_kelas","view_any_kelas","create_kelas","update_kelas","restore_kelas","restore_any_kelas","delete_kelas","delete_any_kelas","force_delete_kelas","force_delete_any_kelas","view_pegawai","view_any_pegawai","create_pegawai","update_pegawai","restore_pegawai","restore_any_pegawai","delete_pegawai","delete_any_pegawai","force_delete_pegawai","force_delete_any_pegawai","view_presensi::pegawai","view_any_presensi::pegawai","create_presensi::pegawai","update_presensi::pegawai","restore_presensi::pegawai","restore_any_presensi::pegawai","delete_presensi::pegawai","delete_any_presensi::pegawai","force_delete_presensi::pegawai","force_delete_any_presensi::pegawai","view_presensi::siswa","view_any_presensi::siswa","create_presensi::siswa","update_presensi::siswa","restore_presensi::siswa","restore_any_presensi::siswa","delete_presensi::siswa","delete_any_presensi::siswa","force_delete_presensi::siswa","force_delete_any_presensi::siswa","view_siswa","view_any_siswa","create_siswa","update_siswa","restore_siswa","restore_any_siswa","delete_siswa","delete_any_siswa","force_delete_siswa","force_delete_any_siswa","view_tahun::pelajaran","view_any_tahun::pelajaran","create_tahun::pelajaran","update_tahun::pelajaran","restore_tahun::pelajaran","restore_any_tahun::pelajaran","delete_tahun::pelajaran","delete_any_tahun::pelajaran","force_delete_tahun::pelajaran","force_delete_any_tahun::pelajaran","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","widget_PresensiPegawai"]},{"name":"guru_sekolah_1","guard_name":"web","permissions":["view_any_presensi::pegawai","view_any_presensi::siswa"]},{"name":"guru_sekolah_2","guard_name":"web","permissions":["view_any_presensi::pegawai","view_any_presensi::siswa"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
