<?php

namespace App\Support;

use App\Models\User;

class HomeRedirect
{
    /**
     * Operator roles that already have a dedicated admin/portal home.
     * Additive module access only redirects to admin.dashboard when none of these apply.
     *
     * @var list<string>
     */
    private const OPERATOR_HOME_ROLES = [
        'bendahara',
        'cashless',
        'prestasi_pelanggaran',
        'perizinan',
        'pimpinan',
    ];

    /**
     * Home dashboard per role. For multi-role users the priority is always
     * admin/super_admin first, then the role with the lowest roles.id
     * (first seeded) decides.
     *
     * @var array<string, string> role name => route name
     */
    private const ROLE_HOME_ROUTES = [
        'bendahara' => 'admin.keuangan.dashboard',
        'cashless' => 'admin.dompet-digital.dashboard',
        'guru' => 'portal.guru.dashboard',
        'orang_tua' => 'portal.ortu.dashboard',
        'siswa' => 'portal.siswa.dashboard',
        'kantin' => 'portal.kantin.dashboard',
        'pimpinan' => 'portal.pimpinan.dashboard',
        'prestasi_pelanggaran' => 'admin.prestasi-pelanggaran.dashboard',
        'perpustakaan' => 'portal.perpustakaan.dashboard',
        'perizinan' => 'portal.perizinan.dashboard',
    ];

    public static function for(?User $user): string
    {
        return route(self::routeName($user));
    }

    public static function routeName(?User $user): string
    {
        if ($user === null) {
            return 'spmb.home';
        }

        // Admin/super_admin always win over any portal/operator role.
        if ($user->hasRole('super_admin')) {
            return 'super-admin.dashboard';
        }

        if ($user->hasRole('admin')) {
            return 'admin.dashboard';
        }

        // Additive module grants (e.g. guru + Manajemen Siswa) open the admin shell.
        if (
            AdminModuleAccess::hasDirectModuleAccess($user)
            && ! $user->hasAnyRole(self::OPERATOR_HOME_ROLES)
        ) {
            return 'admin.dashboard';
        }

        // Any other role: the lowest roles.id decides the dashboard.
        $roleName = $user->roles()->orderBy('id')->value('name');

        return self::ROLE_HOME_ROUTES[$roleName] ?? 'login';
    }
}
