<?php

namespace App\Support;

use App\Models\Guru;
use Illuminate\Database\Eloquent\Builder;

class GuruSekolahFilter
{
    /** Filter admin list by operator school or selected school (empty = all). */
    public static function applyListFilter(Builder $query, ?int $requestedSekolahId): Builder
    {
        $operatorId = AdminSchoolScope::operatorSekolahId();
        if ($operatorId !== null) {
            return $query->where('sekolah_id', $operatorId);
        }

        if ($requestedSekolahId === null) {
            return $query;
        }

        return $query->where('sekolah_id', $requestedSekolahId);
    }

    /** Include unassigned teachers when an operator is scoped to one school. */
    public static function applyAccessibleScope(Builder $query, ?int $operatorSekolahId): Builder
    {
        if ($operatorSekolahId === null) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($operatorSekolahId) {
            $inner->where('sekolah_id', $operatorSekolahId)
                ->orWhereNull('sekolah_id');
        });
    }

    public static function isOutsideOperatorScope(Guru $guru, ?int $operatorSekolahId): bool
    {
        if ($operatorSekolahId === null || $guru->sekolah_id === null) {
            return false;
        }

        return (int) $guru->sekolah_id !== $operatorSekolahId;
    }
}
