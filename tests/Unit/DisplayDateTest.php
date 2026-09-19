<?php

use App\Support\DisplayDate;
use Carbon\Carbon;

test('compact and long display date helpers', function () {
    $value = Carbon::parse('2026-07-06 11:00:00');

    expect(DisplayDate::date($value))->toBe('06/07/2026')
        ->and(DisplayDate::datetime($value))->toBe('06/07/2026 11:00')
        ->and(DisplayDate::longDatetime($value))->toBe('Senin, 6 Juli 2026 11:00')
        ->and(DisplayDate::longDate($value))->toBe('Senin, 6 Juli 2026')
        ->and(DisplayDate::cell($value))->toBe('2026-07-06 11:00:00')
        ->and(DisplayDate::cell('2026-07-06'))->toBe('2026-07-06')
        ->and(DisplayDate::longDatetime(null))->toBe('-');
});
