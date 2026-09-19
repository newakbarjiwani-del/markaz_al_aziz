<?php

use App\Support\PortalGreeting;
use Illuminate\Support\Carbon;

test('portal greeting returns salutation by time of day', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-04 08:00:00'));
    expect(PortalGreeting::salutation())->toBe('Selamat pagi');

    Carbon::setTestNow(Carbon::parse('2026-07-04 13:00:00'));
    expect(PortalGreeting::salutation())->toBe('Selamat siang');

    Carbon::setTestNow(Carbon::parse('2026-07-04 16:00:00'));
    expect(PortalGreeting::salutation())->toBe('Selamat sore');

    Carbon::setTestNow(Carbon::parse('2026-07-04 21:00:00'));
    expect(PortalGreeting::salutation())->toBe('Selamat malam');

    Carbon::setTestNow();
});

test('portal greeting returns tabler icon by time of day', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-04 08:00:00'));
    expect(PortalGreeting::icon())->toBe('sun');

    Carbon::setTestNow(Carbon::parse('2026-07-04 13:00:00'));
    expect(PortalGreeting::icon())->toBe('sun-high');

    Carbon::setTestNow(Carbon::parse('2026-07-04 16:00:00'));
    expect(PortalGreeting::icon())->toBe('sunset-2');

    Carbon::setTestNow(Carbon::parse('2026-07-04 21:00:00'));
    expect(PortalGreeting::icon())->toBe('moon');

    Carbon::setTestNow();
});
