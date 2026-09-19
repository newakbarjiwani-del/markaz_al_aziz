<?php

use App\Models\Sccttran;
use App\Models\SccttranCashless;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('sccttran_cashless mirrors sccttran schema and urut sync', function () {
    $finance = Sccttran::create([
        'CUSTID' => 7700000000012345,
        'METODE' => 'VA',
        'TRXDATE' => now(),
        'KREDIT' => 500000,
    ]);

    $cashless = SccttranCashless::create([
        'CUSTID' => 7700000000012345,
        'METODE' => 'VA',
        'TRXDATE' => now(),
        'KREDIT' => 100000,
    ]);

    expect($finance->fresh()->urut)->toBe($finance->id)
        ->and($cashless->fresh()->urut)->toBe($cashless->id)
        ->and($finance->getTable())->toBe('sccttran')
        ->and($cashless->getTable())->toBe('sccttran_cashless');
});
