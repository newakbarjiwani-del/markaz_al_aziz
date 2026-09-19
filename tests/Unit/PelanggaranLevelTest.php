<?php

use App\Support\PelanggaranLevel;

test('pelanggaran level labels and normalize', function () {
    expect(PelanggaranLevel::labels())->toBe([
        'ringan' => 'Ringan',
        'sedang' => 'Sedang',
        'berat' => 'Berat',
    ])
        ->and(PelanggaranLevel::label(PelanggaranLevel::RINGAN))->toBe('Ringan')
        ->and(PelanggaranLevel::label(PelanggaranLevel::SEDANG))->toBe('Sedang')
        ->and(PelanggaranLevel::label(PelanggaranLevel::BERAT))->toBe('Berat')
        ->and(PelanggaranLevel::label('foo'))->toBe('-')
        ->and(PelanggaranLevel::label(null))->toBe('-')
        ->and(PelanggaranLevel::normalize('Ringan'))->toBe(PelanggaranLevel::RINGAN)
        ->and(PelanggaranLevel::normalize(' SEDANG '))->toBe(PelanggaranLevel::SEDANG)
        ->and(PelanggaranLevel::normalize('berat'))->toBe(PelanggaranLevel::BERAT)
        ->and(PelanggaranLevel::normalize('unknown'))->toBeNull()
        ->and(PelanggaranLevel::normalize(null))->toBeNull();
});

test('pelanggaran level rules are in', function () {
    expect(PelanggaranLevel::rules())
        ->toContain('required')
        ->and(end(PelanggaranLevel::rules()))->toBeInstanceOf(\Illuminate\Validation\Rules\In::class);
});
