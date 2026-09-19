<?php

namespace App\Support;

class PortalGreeting
{
    public static function timeOfDay(): string
    {
        $hour = (int) now()->format('H');

        return match (true) {
            $hour >= 5 && $hour < 11 => 'pagi',
            $hour >= 11 && $hour < 15 => 'siang',
            $hour >= 15 && $hour < 18 => 'sore',
            default => 'malam',
        };
    }

    public static function salutation(): string
    {
        return 'Selamat '.self::timeOfDay();
    }

    public static function icon(): string
    {
        return match (self::timeOfDay()) {
            'pagi' => 'sun',
            'siang' => 'sun-high',
            'sore' => 'sunset-2',
            'malam' => 'moon',
            default => 'hand-wave',
        };
    }
}
