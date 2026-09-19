<?php

use App\Models\Sccttran;
use App\Models\User;
use App\Services\Finance\SccttranLogger;
use App\Services\Finance\SccttranSaldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();

    $this->fixture = FinanceFixtures::schoolWithStudent();
    $this->admin = FinanceFixtures::adminUser(['sekolah_id' => $this->fixture->sekolah->id]);

    $logger = app(SccttranLogger::class);
    $logger->topUp($this->fixture->siswa->id, 500000, [
        'refno' => 'TOP-001',
        'trxdate' => now(),
        'channelid' => 'VA',
        'kodebank' => 'BNI',
    ]);
    $logger->fromInvoice($this->fixture->siswa->id, 200000, [
        'refno' => 'TOP-001',
        'trxdate' => now(),
    ]);
});

test('admin saldo siswa data lists balance from sccttran kredit minus debet', function () {
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.saldo-siswa.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk();

    $row = collect($response->json('data'))->first();
    expect($row)->not->toBeNull()
        ->and($row[0])->toBe($this->fixture->siswa->nis);

    $balanceCell = $row[3];
    $balanceDisplay = is_array($balanceCell) ? ($balanceCell['display'] ?? '') : $balanceCell;
    expect($balanceDisplay)->toBe('Rp 300.000');
});

test('admin saldo siswa show returns computed balance', function () {
    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.saldo-siswa.show', $this->fixture->siswa))
        ->assertOk()
        ->assertJsonPath('data.balance', 300000)
        ->assertJsonPath('data.siswa.nis', $this->fixture->siswa->nis);
});

test('admin saldo siswa transactions endpoint returns sccttran rows', function () {
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.saldo-siswa.transactions', $this->fixture->siswa, [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk()
        ->assertJsonPath('recordsTotal', 2);

    $metodes = collect($response->json('data'))->pluck(1)->all();
    expect($metodes)->toContain('TOP UP', 'FROM INVOICE');
});

test('sccttran saldo service stats aggregate child transactions', function () {
    $stats = app(SccttranSaldoService::class)->statsForSiswaIds([$this->fixture->siswa->id]);

    expect($stats['saldo'])->toBe(300000)
        ->and($stats['total_transaksi'])->toBe(2)
        ->and($stats['bulan_ini_kredit'])->toBe(500000)
        ->and($stats['bulan_ini_debet'])->toBe(200000);
});

test('sccttran saldo service batch balances returns map for multiple students', function () {
    $other = \App\Models\Siswa::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'kelas_id' => $this->fixture->kelas->id,
        'nis' => '1000099',
        'name' => 'Siswa Batch',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    app(SccttranLogger::class)->topUp($other->id, 75000, [
        'refno' => 'TOP-BATCH',
        'trxdate' => now(),
    ]);

    $service = app(SccttranSaldoService::class);
    $map = $service->balancesForSiswaIds([
        $this->fixture->siswa->id,
        $other->id,
        999999,
    ]);

    expect($map)->toBe([
        $this->fixture->siswa->id => 300000,
        $other->id => 75000,
        999999 => 0,
    ])
        ->and($service->globalStats($this->fixture->sekolah->id)['total_saldo'])->toBe(375000)
        ->and($service->globalStats($this->fixture->sekolah->id)['siswa_bersaldo'])->toBe(2);
});

test('portal siswa pembayaran data uses sccttran history', function () {
    $siswaUser = User::create([
        'username' => 'siswa.saldo',
        'name' => $this->fixture->siswa->name,
        'email' => 'siswa-saldo@test.local',
        'password' => bcrypt('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'siswa_id' => $this->fixture->siswa->id,
    ]);
    $siswaUser->assignRole('siswa');

    $response = $this->actingAs($siswaUser)
        ->getJson(route('portal.siswa.pembayaran.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk()
        ->assertJsonPath('recordsTotal', 2);

    expect(collect($response->json('data'))->pluck(1)->all())
        ->toContain('TOP UP', 'FROM INVOICE');
});

test('saldo siswa list only includes students with positive balance', function () {
    $other = \App\Models\Siswa::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'kelas_id' => $this->fixture->kelas->id,
        'nis' => '1000002',
        'name' => 'Siswa Tanpa Saldo',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.saldo-siswa.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
        ]));

    $response->assertOk();

    $nisList = collect($response->json('data'))->pluck(0)->all();
    expect($nisList)->toContain($this->fixture->siswa->nis)
        ->and($nisList)->not->toContain($other->nis);
});

test('manual jurnal saldo writes sccttran row when enabled', function () {
    config(['school.manual_saldo_keuangan_adjustment_enabled' => true]);

    \App\Models\SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 300000,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.saldo-siswa.adjust'), [
            'siswa_id' => $this->fixture->siswa->id,
            'type' => 'tambah',
            'amount' => 50000,
        ])
        ->assertOk();

    expect(Sccttran::where('METODE', 'JURNAL SALDO')->count())->toBe(1)
        ->and(app(SccttranSaldoService::class)->balanceForSiswa($this->fixture->siswa->id))->toBe(350000)
        ->and((float) \App\Models\SaldoKeuangan::where('siswa_id', $this->fixture->siswa->id)->value('balance'))->toBe(350000.0);
});
