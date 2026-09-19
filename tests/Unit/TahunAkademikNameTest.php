<?php

use App\Support\TahunAkademikName;

test('tahun akademik name accepts valid YYYY/YYYY format', function () {
    expect(TahunAkademikName::isValid('2025/2026'))->toBeTrue()
        ->and(TahunAkademikName::isValid(' 2024/2025 '))->toBeTrue();
});

test('tahun akademik name rejects invalid formats', function () {
    expect(TahunAkademikName::isValid('2025-2026'))->toBeFalse()
        ->and(TahunAkademikName::isValid('2025/2027'))->toBeFalse()
        ->and(TahunAkademikName::isValid('25/26'))->toBeFalse()
        ->and(TahunAkademikName::isValid('TA 2025/2026'))->toBeFalse()
        ->and(TahunAkademikName::isValid(''))->toBeFalse();
});
