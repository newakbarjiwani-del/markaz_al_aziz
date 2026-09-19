<?php

use App\Support\TagihanPeriode;

test('normalizes month input to academic-end-year periode', function () {
    expect(TagihanPeriode::normalize('2026-01'))->toBe(202601)
        ->and(TagihanPeriode::normalize('2026-07'))->toBe(202707)
        ->and(TagihanPeriode::normalize(202707))->toBe(202707)
        ->and(TagihanPeriode::normalize('202707'))->toBe(202707);
});

test('rejects invalid periode values', function () {
    expect(TagihanPeriode::normalize('202613'))->toBeNull()
        ->and(TagihanPeriode::normalize('abc'))->toBeNull();
});

test('formats periode for display with academic year', function () {
    expect(TagihanPeriode::display(202601))->toBe('Januari 2026 · TA 2025/2026')
        ->and(TagihanPeriode::display(202707))->toBe('Juli 2026 · TA 2026/2027')
        ->and(TagihanPeriode::display(202801))->toBe('Januari 2028 · TA 2027/2028')
        ->and(TagihanPeriode::display(null))->toBe('-');
});

test('resolves academic year label for july-june calendar', function () {
    expect(TagihanPeriode::academicYearLabel(202607))->toBe('2025/2026')
        ->and(TagihanPeriode::academicYearLabel(202606))->toBe('2025/2026')
        ->and(TagihanPeriode::academicYearLabel(202707))->toBe('2026/2027');
});

test('converts periode to calendar month input', function () {
    expect(TagihanPeriode::toMonthInput(202601))->toBe('2026-01')
        ->and(TagihanPeriode::toMonthInput(202707))->toBe('2026-07');
});

test('encodes calendar months using academic end year', function () {
    expect(TagihanPeriode::fromCalendarMonth(2025, 7))->toBe(202607)
        ->and(TagihanPeriode::fromCalendarMonth(2026, 1))->toBe(202601)
        ->and(TagihanPeriode::fromCalendarMonth(2026, 7))->toBe(202707);
});
