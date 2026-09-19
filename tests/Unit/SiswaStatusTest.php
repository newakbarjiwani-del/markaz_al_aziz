<?php

use App\Support\SiswaStatus;

test('siswa status labels and normalize', function () {
    expect(SiswaStatus::label(SiswaStatus::ACTIVE))->toBe('Aktif')
        ->and(SiswaStatus::label(SiswaStatus::INACTIVE))->toBe('Nonaktif')
        ->and(SiswaStatus::label(SiswaStatus::PENDING))->toBe('Menunggu')
        ->and(SiswaStatus::exportValue(SiswaStatus::ACTIVE))->toBe('aktif')
        ->and(SiswaStatus::exportValue(SiswaStatus::INACTIVE))->toBe('nonaktif')
        ->and(SiswaStatus::normalize('aktif'))->toBe(SiswaStatus::ACTIVE)
        ->and(SiswaStatus::normalize('nonaktif'))->toBe(SiswaStatus::INACTIVE)
        ->and(SiswaStatus::normalize('pending'))->toBe(SiswaStatus::PENDING)
        ->and(SiswaStatus::normalize('1'))->toBe(SiswaStatus::ACTIVE)
        ->and(SiswaStatus::normalize(0))->toBe(SiswaStatus::INACTIVE)
        ->and(SiswaStatus::canTransact(SiswaStatus::ACTIVE))->toBeTrue()
        ->and(SiswaStatus::canTransact(SiswaStatus::INACTIVE))->toBeFalse()
        ->and(SiswaStatus::canTransact(SiswaStatus::PENDING))->toBeFalse();
});
