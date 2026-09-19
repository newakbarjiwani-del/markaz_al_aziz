<?php

namespace App\Support;

use App\Models\User;

class PortalPwa
{
    /** @var list<string> */
    public const PORTAL_ROLES = [
        'guru',
        'orang_tua',
        'siswa',
        'kantin',
        'pimpinan',
        'perpustakaan',
        'perizinan',
    ];

    public static function enabled(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null || ! $user->hasAnyRole(self::PORTAL_ROLES)) {
            return false;
        }

        return request()->routeIs('portal.*');
    }

    /** @return array<string, mixed> */
    public static function manifest(): array
    {
        return [
            'name' => config('pwa.name'),
            'short_name' => config('pwa.short_name'),
            'description' => config('pwa.description'),
            'start_url' => '/portal/',
            'scope' => '/portal/',
            'id' => '/portal/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'lang' => 'id',
            'dir' => 'ltr',
            'theme_color' => config('pwa.theme_color'),
            'background_color' => config('pwa.background_color'),
            'icons' => [
                [
                    'src' => '/pwa/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/pwa/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/pwa/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ];
    }
}
