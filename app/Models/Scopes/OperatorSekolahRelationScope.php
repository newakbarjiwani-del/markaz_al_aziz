<?php

namespace App\Models\Scopes;

use App\Support\AdminSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OperatorSekolahRelationScope implements Scope
{
    public function __construct(
        private readonly string $relation,
        private readonly string $column = 'sekolah_id',
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        $sekolahId = AdminSchoolScope::operatorSekolahId();
        if ($sekolahId === null) {
            return;
        }

        $builder->whereHas($this->relation, fn (Builder $query) => $query->where($this->column, $sekolahId));
    }
}
