<?php

use App\Support\AdminModuleAccess;

test('permissions for finance include potongan bundles', function () {
    $perms = AdminModuleAccess::permissionsForModules(['finance']);

    expect($perms)->toContain('finance.view')
        ->and($perms)->toContain('finance.delete')
        ->and($perms)->toContain('katalog-potongan.view')
        ->and($perms)->toContain('potongan-tagihan.create');
});

test('modules implied by bendahara and cashless roles', function () {
    expect(AdminModuleAccess::modulesImpliedByRoles(['bendahara']))
        ->toEqualCanonicalizing(['finance', 'attendance']);

    expect(AdminModuleAccess::modulesImpliedByRoles(['cashless', 'library']))
        ->toBe(['cashless']);

    expect(AdminModuleAccess::modulesImpliedByRoles(['admin']))
        ->toEqualCanonicalizing(AdminModuleAccess::keys());
});

test('modules blocked for siswa orang_tua admin super_admin', function () {
    expect(AdminModuleAccess::modulesAllowedForRoles(['guru', 'cashless']))->toBeTrue();
    expect(AdminModuleAccess::modulesAllowedForRoles(['siswa']))->toBeFalse();
    expect(AdminModuleAccess::modulesAllowedForRoles(['admin', 'guru']))->toBeFalse();
});
