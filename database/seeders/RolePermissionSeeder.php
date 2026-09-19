<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure roles exist with live-server ids/names before assigning permissions.
        $this->call(SyncServerRolesSeeder::class);

        $modules = [
            'students', 'teachers', 'finance', 'attendance', 'cashless', 'library', 'users', 'master_data', 'spmb', 'akademik', 'ujian', 'booklet', 'alumni', 'tahfidz',
            'prestasi-siswa', 'pelanggaran-siswa', 'prestasi-guru', 'pelanggaran-guru', 'katalog-pelanggaran', 'katalog-prestasi', 'hukuman-siswa', 'perizinan',
            'katalog-potongan', 'potongan-tagihan',
        ];

        $actions = ['view', 'create', 'update', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}"]);
            }
        }

        $role = fn (string $name): Role => Role::findByName($name);

        $role('super_admin')->syncPermissions(Permission::all());

        $role('admin')->syncPermissions(
            Permission::whereIn('name', collect($modules)->flatMap(
                fn ($m) => collect($actions)->map(fn ($a) => "{$m}.{$a}")
            )->all())->get()
        );

        $role('guru')->syncPermissions([
            'attendance.view', 'attendance.update', 'teachers.view', 'library.view',
            'prestasi-siswa.view', 'prestasi-siswa.create', 'prestasi-siswa.update', 'prestasi-siswa.delete',
            'pelanggaran-siswa.view', 'pelanggaran-siswa.create', 'pelanggaran-siswa.update', 'pelanggaran-siswa.delete',
        ]);
        $role('orang_tua')->syncPermissions([
            'students.view', 'finance.view', 'attendance.view', 'cashless.view', 'library.view',
            'prestasi-siswa.view', 'pelanggaran-siswa.view',
        ]);
        $role('siswa')->syncPermissions([
            'attendance.view', 'cashless.view', 'library.view',
            'prestasi-siswa.view', 'pelanggaran-siswa.view',
        ]);
        $role('kantin')->syncPermissions(['cashless.view', 'cashless.create', 'cashless.update']);
        $role('bendahara')->syncPermissions([
            'finance.view',
            'finance.create',
            'finance.update',
            'finance.delete',
            'katalog-potongan.view',
            'katalog-potongan.create',
            'katalog-potongan.update',
            'katalog-potongan.delete',
            'potongan-tagihan.view',
            'potongan-tagihan.create',
            'potongan-tagihan.update',
            'potongan-tagihan.delete',
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.delete',
        ]);
        $role('cashless')->syncPermissions([
            'cashless.view',
            'cashless.create',
            'cashless.update',
            'cashless.delete',
        ]);

        $prestasiPelanggaranPermissions = [
            'prestasi-siswa.view',
            'prestasi-siswa.create',
            'prestasi-siswa.update',
            'prestasi-siswa.delete',
            'pelanggaran-siswa.view',
            'pelanggaran-siswa.create',
            'pelanggaran-siswa.update',
            'pelanggaran-siswa.delete',
            'prestasi-guru.view',
            'prestasi-guru.create',
            'prestasi-guru.update',
            'prestasi-guru.delete',
            'pelanggaran-guru.view',
            'pelanggaran-guru.create',
            'pelanggaran-guru.update',
            'pelanggaran-guru.delete',
            'katalog-pelanggaran.view',
            'katalog-pelanggaran.create',
            'katalog-pelanggaran.update',
            'katalog-pelanggaran.delete',
            'katalog-prestasi.view',
            'katalog-prestasi.create',
            'katalog-prestasi.update',
            'katalog-prestasi.delete',
            'hukuman-siswa.view',
            'hukuman-siswa.create',
            'hukuman-siswa.update',
            'hukuman-siswa.delete',
        ];

        $role('pimpinan')->syncPermissions(array_merge($prestasiPelanggaranPermissions, [
            'attendance.view',
            'finance.view',
            'cashless.view',
            'students.view',
            'teachers.view',
            'library.view',
            'perizinan.view',
        ]));

        $role('prestasi_pelanggaran')->syncPermissions($prestasiPelanggaranPermissions);

        $role('perpustakaan')->syncPermissions([
            'library.view',
            'library.create',
            'library.update',
            'library.delete',
        ]);

        $role('perizinan')->syncPermissions([
            'perizinan.view',
            'perizinan.create',
            'perizinan.update',
            'perizinan.delete',
            'students.view',
            'pelanggaran-siswa.create',
        ]);
    }
}
