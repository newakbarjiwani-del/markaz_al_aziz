<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class AjaxSelect
{
    /** @param  iterable<int, array{id: int|string, text: string}>  $items */
    public static function respond(iterable $items, mixed $legacyData = null): JsonResponse
    {
        $results = collect($items)->values()->all();

        $payload = [
            'results' => $results,
            'pagination' => ['more' => false],
        ];

        if ($legacyData !== null) {
            $payload['data'] = $legacyData;
        }

        return response()->json($payload);
    }

    public static function empty(mixed $legacyData = null): JsonResponse
    {
        return self::respond([], $legacyData);
    }
}
