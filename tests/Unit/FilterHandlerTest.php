<?php

use App\Support\FilterHandler;

test('filter handler strips all and empty values', function () {
    $resolved = FilterHandler::resolveFilters([
        'sekolah' => 'all',
        'kelas' => 'VII-A',
        'tahun_akademik' => '',
        'post' => ['all', 'SPP', ''],
    ], [
        'sekolah' => 'sekolah_id',
        'kelas' => 'kelas_id',
        'tahun_akademik' => 'tahun_akademik_id',
        'post' => 'KodePost',
    ]);

    expect($resolved)->toBe([
        'KodePost' => ['SPP'],
        'kelas_id' => 'VII-A',
    ]);
});

test('filter handler applies sekolah scope', function () {
    $filters = FilterHandler::applySekolahScope([
        'kelas_id' => 3,
    ], 75);

    expect($filters)->toBe([
        'kelas_id' => 3,
        'sekolah_id' => 75,
    ]);
});

test('filter handler skips sekolah scope when empty', function () {
    $filters = FilterHandler::applySekolahScope(['a' => 1], null);

    expect($filters)->toBe(['a' => 1]);
});
