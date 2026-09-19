<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncServerRolesSeeder extends Seeder
{
    /**
     * Sync role master data with **stable live IDs**.
     *
     * - Existing roles are matched by `name` only — their `id` is never changed
     *   (so new roles must not shift others; append the next free id).
     * - Missing roles are inserted with the explicit `id` from {@see serverRoles()}.
     * - Does not touch `model_has_roles` / user assignments.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->serverRoles() as $role) {
            $existing = Role::query()->where('name', $role['name'])->first();

            if ($existing) {
                if ($existing->guard_name !== $role['guard_name']) {
                    $existing->update(['guard_name' => $role['guard_name']]);
                }

                continue;
            }

            // Primary key is guarded on Spatie Role — set id explicitly so
            // auto-increment order never renumbers / shifts other roles.
            $model = new Role([
                'name' => $role['name'],
                'guard_name' => $role['guard_name'],
            ]);
            $model->id = $role['id'];
            $model->save();
        }
    }

    /**
     * Live-server role ids (stable). When adding a role, append with the next
     * free id — never renumber existing rows.
     *
     * @return list<array{id: int, name: string, guard_name: string}>
     */
    private function serverRoles(): array
    {
        return [
            ['id' => 1, 'name' => 'super_admin', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'admin', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'guru', 'guard_name' => 'web'],
            ['id' => 4, 'name' => 'orang_tua', 'guard_name' => 'web'],
            ['id' => 5, 'name' => 'siswa', 'guard_name' => 'web'],
            ['id' => 6, 'name' => 'kantin', 'guard_name' => 'web'],
            ['id' => 7, 'name' => 'pimpinan', 'guard_name' => 'web'],
            ['id' => 8, 'name' => 'perpustakaan', 'guard_name' => 'web'],
            ['id' => 9, 'name' => 'bendahara', 'guard_name' => 'web'],
            ['id' => 10, 'name' => 'cashless', 'guard_name' => 'web'],
            ['id' => 11, 'name' => 'perizinan', 'guard_name' => 'web'],
            ['id' => 12, 'name' => 'prestasi_pelanggaran', 'guard_name' => 'web'],
        ];
    }
}
