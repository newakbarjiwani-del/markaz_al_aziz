<?php

namespace App\Models\Scopes;

use App\Support\AdminSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OperatorSekolahScope implements Scope
{
    public function __construct(
        private readonly string $column = 'sekolah_id',
        private readonly bool $includeGlobal = false,
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        $sekolahId = AdminSchoolScope::operatorSekolahId();
        if ($sekolahId === null) {
            return;
        }

        $qualifiedColumn = str_contains($this->column, '.')
            ? $this->column
            : $model->getTable().'.'.$this->column;

        if ($this->includeGlobal) {
            $builder->where(function (Builder $query) use ($qualifiedColumn, $sekolahId) {
                $query->whereNull($qualifiedColumn)
                    ->orWhere($qualifiedColumn, $sekolahId);
            });

            return;
        }

        $builder->where($qualifiedColumn, $sekolahId);
    }
}
