<?php

use App\Models\SccttranCashless;
use App\Models\Siswa;
use App\Services\Finance\SccttranCashlessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->fixture = FinanceFixtures::schoolWithStudent();
});

test('cashless batch balances returns map for multiple students', function () {
    SccttranCashless::create([
        'CUSTID' => $this->fixture->siswa->id,
        'METODE' => 'TOP UP',
        'TRXDATE' => now(),
        'KREDIT' => 50000,
        'DEBET' => 0,
        'wallet' => 'us',
    ]);

    $other = Siswa::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'kelas_id' => $this->fixture->kelas->id,
        'nis' => '1000098',
        'name' => 'Siswa Cashless Batch',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    SccttranCashless::create([
        'CUSTID' => $other->id,
        'METODE' => 'TOP UP',
        'TRXDATE' => now(),
        'KREDIT' => 25000,
        'DEBET' => 0,
        'wallet' => 'us',
    ]);

    $service = app(SccttranCashlessService::class);
    $map = $service->balancesForSiswaIds([
        $this->fixture->siswa->id,
        $other->id,
        999998,
    ]);

    expect($map)->toBe([
        $this->fixture->siswa->id => 50000,
        $other->id => 25000,
        999998 => 0,
    ])
        ->and($service->globalStats($this->fixture->sekolah->id)['total_saldo'])->toBe(75000)
        ->and($service->globalStats($this->fixture->sekolah->id)['siswa_bersaldo'])->toBe(2);
});

test('admin saldo cashless data lists balance from grouped ledger join', function () {
    SccttranCashless::create([
        'CUSTID' => $this->fixture->siswa->id,
        'METODE' => 'TOP UP',
        'TRXDATE' => now(),
        'KREDIT' => 12000,
        'DEBET' => 0,
        'wallet' => 'us',
    ]);

    $admin = FinanceFixtures::adminUser(['sekolah_id' => $this->fixture->sekolah->id]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.dompet-digital.saldo-cashless.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk();

    $row = collect($response->json('data'))->first();
    expect($row)->not->toBeNull();

    $balanceCell = $row[4];
    $balanceDisplay = is_array($balanceCell) ? ($balanceCell['display'] ?? '') : $balanceCell;
    expect($balanceDisplay)->toBe('Rp 12.000');
});
