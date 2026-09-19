<?php

namespace App\Services;

use App\Models\User;
use App\Support\AdminModuleAccess;
use App\Support\HomeRedirect;

class MenuService
{
    private const PORTAL_MENU_ROLES = [
        'orang_tua' => 'orang-tua',
        'siswa' => 'siswa',
        'guru' => 'guru',
        'kantin' => 'kantin',
        'pimpinan' => 'pimpinan',
        'perpustakaan' => 'perpustakaan',
        'perizinan' => 'perizinan',
    ];

    private const PORTAL_ROLE_LABELS = [
        'guru' => 'Guru',
        'kantin' => 'Kantin',
        'pimpinan' => 'Pimpinan',
        'perpustakaan' => 'Perpustakaan',
        'perizinan' => 'Perizinan',
        'orang_tua' => 'Orang Tua',
        'siswa' => 'Siswa',
    ];

    public function homeRouteFor(?User $user): string
    {
        // Single source of truth — see HomeRedirect (admin first, then roles.id).
        return HomeRedirect::routeName($user);
    }

    public function menuFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $roleNames = $user->roles()->pluck('name')->toArray();
        $menu = [];

        $menu = $this->buildAdminSection($menu, $roleNames, $user);
        $menu = $this->buildPortalSections($menu, $user);

        return $this->withProfileLink($menu);
    }

    /**
     * @param  array<int, array<string, mixed>>  $menu
     * @param  list<string>  $roleNames
     * @return array<int, array<string, mixed>>
     */
    private function buildAdminSection(array $menu, array $roleNames, User $user): array
    {
        if (in_array('admin', $roleNames, true) || in_array('super_admin', $roleNames, true)) {
            return array_merge($menu, $this->filterItems(config('admin-menu'), $user));
        }

        $allowedLabels = [];

        if (in_array('bendahara', $roleNames, true)) {
            $allowedLabels = array_merge($allowedLabels, ['Keuangan', 'Absensi']);
        }

        if (in_array('cashless', $roleNames, true)) {
            $allowedLabels = array_merge($allowedLabels, ['Cashless']);
        }

        if (in_array('pimpinan', $roleNames, true) || in_array('prestasi_pelanggaran', $roleNames, true)) {
            $allowedLabels = array_merge($allowedLabels, ['Prestasi & Pelanggaran']);
        }

        if (in_array('perizinan', $roleNames, true)) {
            $allowedLabels = array_merge($allowedLabels, ['Rekap Perizinan']);
        }

        $allowedLabels = array_values(array_unique(array_merge(
            $allowedLabels,
            AdminModuleAccess::permittedMenuLabels($user)
        )));

        if ($allowedLabels === [] && ! AdminModuleAccess::userCanAccessAdminShell($user)) {
            return $menu;
        }

        if ($allowedLabels !== []) {
            $allowedLabels[] = 'Beranda';
            $allowedLabels = array_values(array_unique($allowedLabels));
        }

        $filtered = collect(config('admin-menu'))
            ->filter(function (array $item) use ($allowedLabels) {
                $label = $item['label'] ?? '';

                return in_array($label, $allowedLabels, true);
            })
            ->values()
            ->all();

        return array_merge($menu, $this->filterItems($filtered, $user));
    }

    /**
     * @param  array<int, array<string, mixed>>  $menu
     * @return array<int, array<string, mixed>>
     */
    private function buildPortalSections(array $menu, User $user): array
    {
        $portalRoles = $user->roles()
            ->whereIn('name', array_keys(self::PORTAL_MENU_ROLES))
            ->orderBy('id')
            ->pluck('name')
            ->toArray();

        foreach ($portalRoles as $roleName) {
            $menu[] = [
                'type' => 'divider',
                'label' => 'Portal: '.(self::PORTAL_ROLE_LABELS[$roleName] ?? $roleName),
            ];

            $configKey = self::PORTAL_MENU_ROLES[$roleName];
            $portalMenu = config("portal-menus.{$configKey}", []);

            $menu = array_merge($menu, $portalMenu);
        }

        return $menu;
    }

    public function bottomNavFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return config('mobile-bottom-nav.admin', []);
        }

        $firstPortalRole = $user->roles()
            ->whereNotIn('name', ['super_admin', 'admin', 'bendahara', 'cashless'])
            ->orderBy('id')
            ->value('name');

        if ($firstPortalRole === null) {
            if ($user->hasRole('bendahara')) {
                return config('mobile-bottom-nav.bendahara', []);
            }

            if ($user->hasRole('cashless')) {
                return config('mobile-bottom-nav.cashless', []);
            }

            return [];
        }

        return config("mobile-bottom-nav.{$firstPortalRole}", []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $menu
     * @return array<int, array<string, mixed>>
     */
    private function withProfileLink(array $menu): array
    {
        foreach ($menu as $item) {
            if (($item['route'] ?? null) === 'profile.show') {
                return $menu;
            }
        }

        $menu[] = [
            'label' => 'Profil Akun',
            'route' => 'profile.show',
            'icon' => 'settings',
        ];

        return $menu;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function filterItems(array $items, User $user): array
    {
        return collect($items)
            ->map(function (array $item) use ($user) {
                if (($item['local_only'] ?? false) && ! app()->isLocal()) {
                    return null;
                }

                if (isset($item['roles']) && ! $user->hasAnyRole($item['roles'])) {
                    return null;
                }

                if (isset($item['children'])) {
                    $item['children'] = $this->filterItems($item['children'], $user);

                    if ($item['children'] === []) {
                        return null;
                    }
                }

                return $item;
            })
            ->filter()
            ->values()
            ->all();
    }
}
