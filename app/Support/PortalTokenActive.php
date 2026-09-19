<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class PortalTokenActive
{
    public static function fromExpiresAt(mixed $expiresAt): bool
    {
        if ($expiresAt === null || $expiresAt === '') {
            return false;
        }

        return Carbon::parse($expiresAt)->isFuture();
    }
}
