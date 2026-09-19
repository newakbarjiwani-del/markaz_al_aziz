<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class AdminModuleAccess
{
    public const ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Admin shell roles that already open /admin without direct module grants.
     *
     * @var list<string>
     */
    public const ADMIN_SHELL_ROLES = [
        'admin',
        'super_admin',
        'bendahara',
        'cashless',
        'perizinan',
        'pimpinan',
        'prestasi_pelanggaran',
    ];

    /**
     * Roles that must not receive additive module grants.
     *
     * @var list<string>
     */
    public const MODULE_GRANT_BLOCKED_ROLES = [
        'siswa',
        'orang_tua',
        'super_admin',
        'admin',
    ];

    /**
     * Menu key => [label, gate permission, permission prefixes for CRUD bundles].
     *
     * @var array<string, array{label: string, gate: string, prefixes: list<string>}>
     */
    private const MODULES = [
        'master_data' => [
            'label' => 'Master Data',
            'gate' => 'master_data.view',
            'prefixes' => ['master_data'],
        ],
        'students' => [
            'label' => 'Manajemen Siswa',
            'gate' => 'students.view',
            'prefixes' => ['students'],
        ],
        'teachers' => [
            'label' => 'Manajemen Guru',
            'gate' => 'teachers.view',
            'prefixes' => ['teachers'],
        ],
        'prestasi_pelanggaran' => [
            'label' => 'Prestasi & Pelanggaran',
            'gate' => 'prestasi-siswa.view',
            'prefixes' => [
                'prestasi-siswa',
                'pelanggaran-siswa',
                'prestasi-guru',
                'pelanggaran-guru',
                'katalog-pelanggaran',
                'katalog-prestasi',
                'hukuman-siswa',
            ],
        ],
        'finance' => [
            'label' => 'Keuangan',
            'gate' => 'finance.view',
            'prefixes' => ['finance', 'katalog-potongan', 'potongan-tagihan'],
        ],
        'attendance' => [
            'label' => 'Absensi',
            'gate' => 'attendance.view',
            'prefixes' => ['attendance'],
        ],
        'cashless' => [
            'label' => 'Cashless',
            'gate' => 'cashless.view',
            'prefixes' => ['cashless'],
        ],
        'library' => [
            'label' => 'Perpustakaan',
            'gate' => 'library.view',
            'prefixes' => ['library'],
        ],
        'perizinan' => [
            'label' => 'Rekap Perizinan',
            'gate' => 'perizinan.view',
            'prefixes' => ['perizinan'],
        ],
        'spmb' => [
            'label' => 'SPMB',
            'gate' => 'spmb.view',
            'prefixes' => ['spmb'],
        ],
        'akademik' => [
            'label' => 'Akademik',
            'gate' => 'akademik.view',
            'prefixes' => ['akademik'],
        ],
        'ujian' => [
            'label' => 'Ujian Online',
            'gate' => 'ujian.view',
            'prefixes' => ['ujian'],
        ],
        'booklet' => [
            'label' => 'Booklet Sekolah',
            'gate' => 'booklet.view',
            'prefixes' => ['booklet'],
        ],
        'alumni' => [
            'label' => 'Alumni',
            'gate' => 'alumni.view',
            'prefixes' => ['alumni'],
        ],
        'tahfidz' => [
            'label' => 'Tahfidz',
            'gate' => 'tahfidz.view',
            'prefixes' => ['tahfidz'],
        ],
    ];

    /**
     * Role name => module keys already covered by that role for admin menus.
     *
     * @var array<string, list<string>>
     */
    private const ROLE_IMPLIED_MODULES = [
        'admin' => null, // all
        'super_admin' => null,
        'bendahara' => ['finance', 'attendance'],
        'cashless' => ['cashless'],
        'prestasi_pelanggaran' => ['prestasi_pelanggaran'],
        'pimpinan' => ['prestasi_pelanggaran'],
        'perpustakaan' => ['library'],
        'perizinan' => ['perizinan'],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::MODULES);
    }

    /**
     * @return array<string, array{label: string, gate: string, prefixes: list<string>}>
     */
    public static function definitions(): array
    {
        return self::MODULES;
    }

    public static function label(string $key): ?string
    {
        return self::MODULES[$key]['label'] ?? null;
    }

    public static function gate(string $key): ?string
    {
        return self::MODULES[$key]['gate'] ?? null;
    }

    public static function keyForMenuLabel(string $label): ?string
    {
        foreach (self::MODULES as $key => $def) {
            if ($def['label'] === $label) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function gatePermissions(): array
    {
        return array_values(array_unique(array_column(self::MODULES, 'gate')));
    }

    /**
     * @param  list<string>  $moduleKeys
     * @return list<string>
     */
    public static function permissionsForModules(array $moduleKeys): array
    {
        $permissions = [];

        foreach (array_unique($moduleKeys) as $key) {
            if (! isset(self::MODULES[$key])) {
                continue;
            }

            foreach (self::MODULES[$key]['prefixes'] as $prefix) {
                foreach (self::ACTIONS as $action) {
                    $permissions[] = "{$prefix}.{$action}";
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        return self::permissionsForModules(self::keys());
    }

    /**
     * @param  list<string>  $roleNames
     */
    public static function modulesAllowedForRoles(array $roleNames): bool
    {
        if ($roleNames === []) {
            return false;
        }

        return array_intersect($roleNames, self::MODULE_GRANT_BLOCKED_ROLES) === [];
    }

    /**
     * Module keys already covered by selected roles (UI: checked + disabled).
     *
     * @param  list<string>  $roleNames
     * @return list<string>
     */
    public static function modulesImpliedByRoles(array $roleNames): array
    {
        $implied = [];

        foreach ($roleNames as $role) {
            if (! array_key_exists($role, self::ROLE_IMPLIED_MODULES)) {
                continue;
            }

            $modules = self::ROLE_IMPLIED_MODULES[$role];
            if ($modules === null) {
                return self::keys();
            }

            $implied = array_merge($implied, $modules);
        }

        return array_values(array_unique($implied));
    }

    /**
     * Direct (user-assigned) module keys, not role-bundled.
     *
     * @return list<string>
     */
    public static function modulesFromDirectPermissions(User $user): array
    {
        $direct = $user->getDirectPermissions()->pluck('name')->all();

        return self::modulesFromPermissionNames($direct);
    }

    /**
     * @param  list<string>  $permissionNames
     * @return list<string>
     */
    public static function modulesFromPermissionNames(array $permissionNames): array
    {
        $set = array_fill_keys($permissionNames, true);
        $keys = [];

        foreach (self::MODULES as $key => $def) {
            $gate = $def['gate'];
            if (isset($set[$gate])) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public static function userCanAccessAdminShell(User $user): bool
    {
        if ($user->hasAnyRole(self::ADMIN_SHELL_ROLES)) {
            return true;
        }

        // Portal roles may share permission names (e.g. guru → library.view).
        // Only explicit direct module grants open the admin shell.
        return self::hasDirectModuleAccess($user);
    }

    public static function hasDirectModuleAccess(User $user): bool
    {
        return self::modulesFromDirectPermissions($user) !== [];
    }

    /**
     * Menu group labels unlocked by direct module grants (not role-bundled perms).
     *
     * @return list<string>
     */
    public static function permittedMenuLabels(User $user): array
    {
        $labels = [];

        foreach (self::modulesFromDirectPermissions($user) as $key) {
            if (isset(self::MODULES[$key])) {
                $labels[] = self::MODULES[$key]['label'];
            }
        }

        return $labels;
    }

    /**
     * Merge selected module directs with any non-allowlisted direct permissions.
     *
     * @param  list<string>  $moduleKeys
     * @return list<string>
     */
    public static function syncDirectPermissionNames(User $user, array $moduleKeys): array
    {
        $allowlisted = array_fill_keys(self::allPermissionNames(), true);
        $keepOutside = $user->getDirectPermissions()
            ->pluck('name')
            ->reject(fn (string $name) => isset($allowlisted[$name]))
            ->values()
            ->all();

        return array_values(array_unique(array_merge(
            $keepOutside,
            self::permissionsForModules($moduleKeys)
        )));
    }

    /**
     * @param  list<string>  $roleNames
     * @return Collection<int, string>
     */
    public static function permissionNamesForRoles(array $roleNames): Collection
    {
        if ($roleNames === []) {
            return collect();
        }

        return Role::query()
            ->whereIn('name', $roleNames)
            ->with('permissions:id,name')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values();
    }
}
