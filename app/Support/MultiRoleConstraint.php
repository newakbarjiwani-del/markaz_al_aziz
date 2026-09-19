<?php

namespace App\Support;

use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

class MultiRoleConstraint implements ValidationRule
{
    public const SINGLE_ROLES = ['super_admin', 'orang_tua', 'siswa'];

    public const MULTI_ROLES = ['admin', 'guru', 'kantin', 'bendahara', 'cashless', 'pimpinan', 'prestasi_pelanggaran', 'perpustakaan', 'perizinan'];

    /**
     * @param  array<int, string>  $roles
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('The :attribute must be an array.');

            return;
        }

        $value = array_values(array_filter($value));

        if ($value === []) {
            $fail('The :attribute must have at least one role.');

            return;
        }

        $singleRoles = array_intersect($value, self::SINGLE_ROLES);

        if (count($singleRoles) > 1) {
            $fail('Role single-role (Super Admin / Orang Tua / Siswa) hanya boleh dipilih satu saja dan tidak dapat dikombinasikan dengan role lain.');

            return;
        }

        if (count($singleRoles) === 1 && count($value) > 1) {
            $fail('Role single-role (Super Admin / Orang Tua / Siswa) tidak dapat dikombinasikan dengan role lain.');

            return;
        }
    }

    /**
     * Check if the given roles array contains a single-role-only role.
     *
     * @param  array<int, string>  $roles
     */
    public static function hasSingleRole(array $roles): bool
    {
        return array_intersect($roles, self::SINGLE_ROLES) !== [];
    }

    /**
     * @param  array<int, string>  $roles
     */
    public static function isValidCombination(array $roles): bool
    {
        $roles = array_values(array_filter($roles));

        if ($roles === []) {
            return false;
        }

        $singleRoles = array_intersect($roles, self::SINGLE_ROLES);

        return count($singleRoles) <= 1 && (count($singleRoles) === 0 || count($roles) === 1);
    }
}
