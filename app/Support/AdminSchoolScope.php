<?php

namespace App\Support;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class AdminSchoolScope
{
    /**
     * Roles that are school-scoped operators (admin-side). Pure portal identity
     * roles (orang_tua, siswa, guru) are intentionally excluded — they are
     * scoped by their linked entity (children / self / teaching scope), never
     * by users.sekolah_id.
     *
     * @var list<string>
     */
    private const OPERATOR_ROLES = [
        'admin',
        'bendahara',
        'cashless',
        'perpustakaan',
        'kantin',
        'pimpinan',
        'prestasi_pelanggaran',
        'perizinan',
    ];

    /**
     * School ID for a school-assigned operator; null for super_admin, unscoped
     * users, and pure portal roles (identity-scoped).
     */
    public static function operatorSekolahId(?User $user = null): ?int
    {
        $user ??= auth()->user();

        if (! $user || $user->hasRole('super_admin')) {
            return null;
        }

        // Pure portal roles (orang_tua, siswa, guru) are identity-scoped via
        // PortalAccess / GuruTeachingScope. A parent must see every linked
        // child even when those children span schools; scoping them by
        // users.sekolah_id would silently hide cross-school data (the "portal
        // sometimes lists less/more student" bug).
        if (! $user->hasAnyRole(self::OPERATOR_ROLES)) {
            return null;
        }

        if ($user->hasRole('perizinan')) {
            return self::perizinanOperatorSekolahId($user);
        }

        return filled($user->sekolah_id) ? (int) $user->sekolah_id : null;
    }

    /**
     * Portal/admin perizinan: linked guru without sekolah = lintas sekolah
     * (ignore users.sekolah_id). Linked guru with sekolah scopes to that unit.
     * Standalone perizinan (no guru_id) uses users.sekolah_id only.
     */
    private static function perizinanOperatorSekolahId(User $user): ?int
    {
        if ($user->guru_id) {
            // Avoid OperatorSekolahScope on Guru while resolving scope (infinite recursion
            // when the logged-in user has role perizinan + guru_id).
            $guruSekolahId = Guru::withoutGlobalScopes()
                ->whereKey($user->guru_id)
                ->value('sekolah_id');
            if ($guruSekolahId === null) {
                return null;
            }

            return (int) $guruSekolahId;
        }

        return filled($user->sekolah_id) ? (int) $user->sekolah_id : null;
    }

    public static function apply(Builder $query, string $column = 'sekolah_id'): Builder
    {
        $sekolahId = self::operatorSekolahId();
        if ($sekolahId === null) {
            return $query;
        }

        return $query->where($column, $sekolahId);
    }

    public static function applyRelation(Builder $query, string $relation, string $column = 'sekolah_id'): Builder
    {
        $sekolahId = self::operatorSekolahId();
        if ($sekolahId === null) {
            return $query;
        }

        return $query->whereHas($relation, fn (Builder $relationQuery) => $relationQuery->where($column, $sekolahId));
    }

    public static function applyWithGlobal(Builder $query, string $column = 'sekolah_id'): Builder
    {
        $sekolahId = self::operatorSekolahId();
        if ($sekolahId === null) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($column, $sekolahId) {
            $inner->whereNull($column)->orWhere($column, $sekolahId);
        });
    }

    public static function resolveForStore(?Request $request = null): int
    {
        $scoped = self::operatorSekolahId($request?->user());
        if ($scoped !== null) {
            return $scoped;
        }

        $user = $request?->user() ?? auth()->user();

        return (int) ($request?->input('sekolah_id') ?? $user?->sekolah_id ?? 1);
    }

    public static function resolveOptionalFromRequest(?Request $request = null, string $field = 'sekolah_id'): ?int
    {
        $scoped = self::operatorSekolahId($request?->user());
        if ($scoped !== null) {
            return $scoped;
        }

        if ($request === null || ! $request->filled($field)) {
            return null;
        }

        return $request->integer($field);
    }

    /** @return Collection<int, Sekolah> */
    public static function schools(): Collection
    {
        $query = Sekolah::query()->orderBy('name');
        $sekolahId = self::operatorSekolahId();
        if ($sekolahId !== null) {
            $query->whereKey($sekolahId);
        }

        return $query->get();
    }

    /** @return Collection<int, Kelas> */
    public static function kelasList(): Collection
    {
        $query = Kelas::query()->orderBy('name');
        self::apply($query);

        return $query->get();
    }

    /** @return array<int, string> */
    public static function classLabels(): array
    {
        return self::kelasList()->pluck('name', 'id')->all();
    }
}
