<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class SoftDeleteRules
{
    public static function unique(string $table, string $column, mixed $ignore = null, ?string $ignoreColumn = 'id'): Unique
    {
        $rule = Rule::unique($table, $column)->withoutTrashed();

        if ($ignore instanceof \Illuminate\Database\Eloquent\Model) {
            $rule->ignore($ignore, $ignoreColumn);

            return $rule;
        }

        if ($ignore !== null && $ignore !== '' && (int) $ignore > 0) {
            $rule->ignore((int) $ignore, $ignoreColumn);
        }

        return $rule;
    }

    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->withoutTrashed();
    }
}
