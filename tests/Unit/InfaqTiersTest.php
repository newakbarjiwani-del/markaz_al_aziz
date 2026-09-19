<?php

use App\Support\InfaqTiers;
use Tests\TestCase;

uses(TestCase::class);

test('returns off mode by default', function () {
    config(['finance.infaq_mode' => 'off']);
    expect(InfaqTiers::mode())->toBe('off')
        ->and(InfaqTiers::isEnabled())->toBeFalse()
        ->and(InfaqTiers::isOptional())->toBeFalse();
});

test('returns on mode as enabled but not optional', function () {
    config(['finance.infaq_mode' => 'on']);
    expect(InfaqTiers::mode())->toBe('on')
        ->and(InfaqTiers::isEnabled())->toBeTrue()
        ->and(InfaqTiers::isOptional())->toBeFalse();
});

test('returns optional mode as enabled and optional', function () {
    config(['finance.infaq_mode' => 'optional']);
    expect(InfaqTiers::mode())->toBe('optional')
        ->and(InfaqTiers::isEnabled())->toBeTrue()
        ->and(InfaqTiers::isOptional())->toBeTrue();
});

test('returns configured max', function () {
    config(['finance.infaq_max' => 5000]);
    expect(InfaqTiers::max())->toBe(5000);
});

test('returns default max from config when not overridden', function () {
    config(['finance.infaq_max' => 10000]);
    expect(InfaqTiers::max())->toBe(10000);
});

test('returns configured tiers', function () {
    $tiers = [
        ['min' => 50000, 'max' => 200000, 'amount' => 2000],
    ];
    config(['finance.infaq_tiers' => $tiers]);
    expect(InfaqTiers::tiers())->toBe($tiers);
});

test('returns empty tiers when not configured', function () {
    config(['finance.infaq_tiers' => []]);
    expect(InfaqTiers::tiers())->toBeEmpty();
});

test('calculates infaq for amount in tier range', function () {
    config([
        'finance.infaq_mode' => 'on',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
            ['min' => 200001, 'max' => 500000, 'amount' => 3000],
        ],
    ]);

    expect(InfaqTiers::calculate(50000))->toBe(2000)
        ->and(InfaqTiers::calculate(100000))->toBe(2000)
        ->and(InfaqTiers::calculate(200000))->toBe(2000)
        ->and(InfaqTiers::calculate(200001))->toBe(3000)
        ->and(InfaqTiers::calculate(500000))->toBe(3000);
});

test('calculates zero for amount below all tiers', function () {
    config([
        'finance.infaq_mode' => 'on',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
        ],
    ]);

    expect(InfaqTiers::calculate(0))->toBe(0)
        ->and(InfaqTiers::calculate(49999))->toBe(0);
});

test('calculates zero when mode is off', function () {
    config([
        'finance.infaq_mode' => 'off',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
        ],
    ]);

    expect(InfaqTiers::calculate(100000))->toBe(0);
});

test('caps infaq at max', function () {
    config([
        'finance.infaq_mode' => 'on',
        'finance.infaq_max' => 5000,
        'finance.infaq_tiers' => [
            ['min' => 1000000, 'max' => null, 'amount' => 10000],
        ],
    ]);

    expect(InfaqTiers::calculate(1000001))->toBe(5000);
});

test('handles open-ended tier (null max)', function () {
    config([
        'finance.infaq_mode' => 'on',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 1000001, 'max' => null, 'amount' => 10000],
        ],
    ]);

    expect(InfaqTiers::calculate(1000001))->toBe(10000)
        ->and(InfaqTiers::calculate(5000000))->toBe(10000);
});

test('allTiers applies max cap', function () {
    config([
        'finance.infaq_max' => 5000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
            ['min' => 1000001, 'max' => null, 'amount' => 10000],
        ],
    ]);

    $result = InfaqTiers::allTiers();
    expect($result[0]['amount'])->toBe(2000)
        ->and($result[1]['amount'])->toBe(5000);
});

test('allTiers preserves null max', function () {
    config([
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 1000001, 'max' => null, 'amount' => 10000],
        ],
    ]);

    $result = InfaqTiers::allTiers();
    expect($result[0]['max'])->toBeNull();
});

test('default tiers from config match expected structure', function () {
    $tiers = InfaqTiers::allTiers();
    expect($tiers)->not->toBeEmpty();

    foreach ($tiers as $tier) {
        expect($tier)->toHaveKeys(['min', 'max', 'amount'])
            ->and($tier['min'])->toBeInt()
            ->and($tier['amount'])->toBeInt();
    }
});
