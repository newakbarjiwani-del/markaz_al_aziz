<?php

use App\Support\KelasJsonCatalog;
use Tests\TestCase;

uses(TestCase::class);

test('kelas json catalog skips ICT test schools', function () {
    $rows = KelasJsonCatalog::rows();

    expect($rows)->not->toBeEmpty();

    foreach ($rows as $row) {
        expect($row['school_code'])->toBeIn(['paud', 'mts', 'ma', 'takhasus'])
            ->and($row['server_sekolah_id'])->toBeLessThanOrEqual(4);
    }

    expect(collect($rows)->where('school_code', 'paud')->count())->toBe(3)
        ->and(collect($rows)->where('school_code', 'takhasus')->count())->toBe(99)
        ->and(collect($rows)->where('school_code', 'ma')->count())->toBe(5)
        ->and(collect($rows)->where('school_code', 'mts')->count())->toBe(6)
        ->and(KelasJsonCatalog::totalSeededClassCount())->toBe(113);
});

test('kelas json catalog parses roman class names', function () {
    $rows = KelasJsonCatalog::rows();

    $ibnuHajar = collect($rows)->firstWhere('name', 'IX 01-IBNU HAJAR');
    $viiiB = collect($rows)->firstWhere('name', 'VIII B');

    expect($ibnuHajar)->not->toBeNull()
        ->and($ibnuHajar['kelas'])->toBe('IX')
        ->and($ibnuHajar['kelompok'])->toBe('01-IBNU HAJAR')
        ->and($viiiB)->not->toBeNull()
        ->and($viiiB['kelas'])->toBe('VIII')
        ->and($viiiB['kelompok'])->toBe('B');
});
