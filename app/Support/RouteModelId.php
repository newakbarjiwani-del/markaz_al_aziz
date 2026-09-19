<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class RouteModelId
{
    public static function resolve(Request $request, string $parameter): ?int
    {
        $value = $request->route($parameter);

        if ($value instanceof Model) {
            $key = $value->getKey();

            return is_numeric($key) ? (int) $key : null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_object($value) && isset($value->id) && is_numeric($value->id)) {
            return (int) $value->id;
        }

        return null;
    }

    public static function require(Request $request, string $parameter, string $message = 'Data tidak valid untuk pembaruan.'): int
    {
        $id = self::resolve($request, $parameter);

        if ($id === null || $id < 1) {
            throw ValidationException::withMessages([
                $parameter => $message,
            ]);
        }

        return $id;
    }
}
