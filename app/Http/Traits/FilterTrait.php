<?php

namespace App\Http\Traits;

use App\Support\AdminSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait FilterTrait
{
    protected function classesList(): Collection
    {
        return AdminSchoolScope::kelasList();
    }

    protected function applyKelasFilter(Builder $query, Request $request, string $relation = 'siswa'): Builder
    {
        if (! $request->filled('kelas_id')) {
            return $query;
        }

        if ($relation === '_self') {
            return $query->where('kelas_id', $request->kelas_id);
        }

        if (str_contains($relation, '.')) {
            [$parent, $child] = explode('.', $relation, 2);

            return $query->whereHas($parent, function (Builder $q) use ($child, $request) {
                $q->whereHas($child, fn (Builder $sq) => $sq->where('kelas_id', $request->kelas_id));
            });
        }

        return $query->whereHas($relation, fn (Builder $q) => $q->where('kelas_id', $request->kelas_id));
    }

    protected function applySiswaSearchFilter(Builder $query, Request $request, string $relation = 'siswa'): Builder
    {
        if (! $request->filled('q')) {
            return $query;
        }

        $term = trim($request->string('q')->toString());

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        if ($relation === '_self') {
            return $query->where(function (Builder $q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('nis', 'like', $like);
            });
        }

        return $query->whereHas($relation, function (Builder $q) use ($like) {
            $q->where(function (Builder $sq) use ($like) {
                $sq->where('name', 'like', $like)
                    ->orWhere('nis', 'like', $like);
            });
        });
    }

    protected function applyGuruSearchFilter(Builder $query, Request $request, string $relation = 'guru'): Builder
    {
        if (! $request->filled('q')) {
            return $query;
        }

        $term = trim($request->string('q')->toString());

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        if ($relation === '_self') {
            return $query->where(function (Builder $q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('nip', 'like', $like)
                    ->orWhere('jabatan', 'like', $like);
            });
        }

        return $query->whereHas($relation, function (Builder $q) use ($like) {
            $q->where(function (Builder $sq) use ($like) {
                $sq->where('name', 'like', $like)
                    ->orWhere('nip', 'like', $like)
                    ->orWhere('jabatan', 'like', $like);
            });
        });
    }

    protected function applyDateRange(Builder $query, Request $request, string $column, string $fromParam = 'date_from', string $toParam = 'date_to'): Builder
    {
        return $query
            ->when($request->filled($fromParam), fn (Builder $q) => $q->whereDate($column, '>=', $request->input($fromParam)))
            ->when($request->filled($toParam), fn (Builder $q) => $q->whereDate($column, '<=', $request->input($toParam)));
    }
}
