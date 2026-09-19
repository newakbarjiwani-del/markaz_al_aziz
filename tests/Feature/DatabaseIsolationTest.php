<?php

use Illuminate\Support\Facades\DB;

test('tests use isolated sqlite in-memory database', function () {
    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(app()->environment())->toBe('testing')
        ->and(DB::connection()->getDriverName())->toBe('sqlite');
});
