<?php

use App\Models\Dompet;
use App\Models\OrangTua;
use App\Models\SaldoKeuangan;
use App\Models\Sccttran;
use App\Models\SccttranCashless;
use App\Models\SmTopup;
use App\Models\User;
use App\Services\Finance\SccttranLogger;
use App\Support\SiswaStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    config(['finance.biaya_admin_pindah_saldo' => 1000]);

    $this->fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '1000501',
            'name' => 'Siswa Ortu Pindah',
        ],
    ]);

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'nama_ayah' => 'Bapak Pindah',
        'nama_ibu' => 'Ibu Pindah',
        'status' => 'aktif',
    ]);
    $this->orangTua->siswa()->attach($this->fixture->siswa->id);

    $this->ortuUser = User::create([
        'username' => 'ortu.pindah',
        'name' => $this->orangTua->displayName(),
        'email' => 'ortu-pindah@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'orang_tua_id' => $this->orangTua->id,
    ]);
    $this->ortuUser->assignRole('orang_tua');

    app(SccttranLogger::class)->topUp($this->fixture->siswa->id, 500000, [
        'refno' => 'ORTU-TOP-001',
        'trxdate' => now(),
    ]);
    SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 500000,
    ]);
    Dompet::create([
        'siswa_id' => $this->fixture->siswa->id,
        'saldo_us' => 0,
        'saldo_kantin' => 0,
        'saldo_tabungan' => 0,
    ]);
});

test('parent can open pindah saldo page for children', function () {
    $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.pindah-saldo'))
        ->assertOk()
        ->assertSee('Pindah Saldo')
        ->assertSee('Saldo Keuangan')
        ->assertSee('Saldo Cashless')
        ->assertSee('Siswa Ortu Pindah');
});

test('parent can read child finance saldo for pindah preview', function () {
    $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.pindah-saldo.saldo', [
            'siswa_id' => $this->fixture->siswa->id,
        ]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.balance', 500000)
        ->assertJsonPath('data.cashless_balance', 0);
});

test('parent can transfer child finance saldo to cashless', function () {
    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 100000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['saldo_keuangan', 'saldo_cashless', 'refno']]);

    expect($response->json('data.saldo_keuangan'))->toBe(400000);
    expect(Sccttran::where('METODE', 'PINDAH SALDO')->where('DEBET', 100000)->count())->toBe(1);
    expect(SccttranCashless::where('METODE', 'PINDAH SALDO')->where('KREDIT', 100000)->count())->toBe(1);
    expect(SccttranCashless::where('METODE', 'ADMIN FEE')->where('DEBET', 1000)->count())->toBe(1);
    expect(SmTopup::where('CUSTID', $this->fixture->siswa->id)->where('NOMINAL', 100000)->count())->toBe(1);
    expect((float) Dompet::where('siswa_id', $this->fixture->siswa->id)->value('saldo_us'))->toBe(100000.0);
    expect((float) SaldoKeuangan::where('siswa_id', $this->fixture->siswa->id)->value('balance'))->toBe(400000.0);
});

test('parent cannot transfer when finance saldo is insufficient', function () {
    $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 500000,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect(Sccttran::where('METODE', 'PINDAH SALDO')->count())->toBe(0);
});

test('parent cannot transfer for unlinked child', function () {
    $other = FinanceFixtures::schoolWithStudent([
        'sekolah' => ['code' => 'mts', 'name' => 'MTs Other'],
        'siswa' => [
            'nis' => '1000502',
            'name' => 'Anak Lain',
        ],
    ]);

    app(SccttranLogger::class)->topUp($other->siswa->id, 500000, [
        'refno' => 'ORTU-TOP-002',
        'trxdate' => now(),
    ]);

    $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $other->siswa->id,
            'amount' => 100000,
        ])
        ->assertForbidden();
});

test('parent cannot transfer when child cannot transact', function () {
    $this->fixture->siswa->update(['status' => SiswaStatus::INACTIVE]);

    $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 100000,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect(Sccttran::where('METODE', 'PINDAH SALDO')->count())->toBe(0);
});

test('infaq on mode auto-deducts calculated infaq from cashless', function () {
    config([
        'finance.infaq_mode' => 'on',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
        ],
    ]);

    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 100000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.infaq'))->toBe(2000);

    expect(SccttranCashless::where('METODE', 'INFAQ')->where('DEBET', 2000)->count())->toBe(1);
    expect(SccttranCashless::where('METODE', 'PINDAH SALDO')->where('KREDIT', 100000)->count())->toBe(1);
    expect(SccttranCashless::where('METODE', 'ADMIN FEE')->where('DEBET', 1000)->count())->toBe(1);

    $dompet = Dompet::where('siswa_id', $this->fixture->siswa->id)->first();
    expect((float) $dompet->saldo_us)->toBe(100000.0 - 2000.0);
});

test('infaq off mode does not deduct infaq', function () {
    config(['finance.infaq_mode' => 'off']);

    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 100000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.infaq'))->toBe(0);
    expect(SccttranCashless::where('METODE', 'INFAQ')->count())->toBe(0);

    $dompet = Dompet::where('siswa_id', $this->fixture->siswa->id)->first();
    expect((float) $dompet->saldo_us)->toBe(100000.0);
});

test('infaq optional mode with checkbox checked deducts infaq', function () {
    config([
        'finance.infaq_mode' => 'optional',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
        ],
    ]);

    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 100000,
            'infaq_amount' => 2000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.infaq'))->toBe(2000);
    expect(SccttranCashless::where('METODE', 'INFAQ')->where('DEBET', 2000)->count())->toBe(1);
});

test('infaq optional mode without infaq_amount skips infaq', function () {
    config([
        'finance.infaq_mode' => 'optional',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
        ],
    ]);

    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 100000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.infaq'))->toBe(0);
    expect(SccttranCashless::where('METODE', 'INFAQ')->count())->toBe(0);
});

test('infaq optional mode with zero infaq_amount skips infaq', function () {
    config([
        'finance.infaq_mode' => 'optional',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
        ],
    ]);

    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 100000,
            'infaq_amount' => 0,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.infaq'))->toBe(0);
    expect(SccttranCashless::where('METODE', 'INFAQ')->count())->toBe(0);
});

test('infaq amount is capped at configured max', function () {
    config([
        'finance.infaq_mode' => 'on',
        'finance.infaq_max' => 5000,
        'finance.infaq_tiers' => [
            ['min' => 1000000, 'max' => null, 'amount' => 10000],
        ],
    ]);

    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 100000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.infaq'))->toBe(0);
});

test('infaq on mode with amount below all tiers calculates zero', function () {
    config([
        'finance.infaq_mode' => 'on',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
        ],
    ]);

    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.pindah-saldo.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 10000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.infaq'))->toBe(0);
    expect(SccttranCashless::where('METODE', 'INFAQ')->count())->toBe(0);
});

test('infaq page shows infaq-related content when enabled', function () {
    config([
        'finance.infaq_mode' => 'optional',
        'finance.infaq_max' => 10000,
        'finance.infaq_tiers' => [
            ['min' => 50000, 'max' => 200000, 'amount' => 2000],
        ],
    ]);

    $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.pindah-saldo'))
        ->assertOk()
        ->assertSee('Nominal Infaq')
        ->assertSee('berinfaq')
        ->assertSee('50.000');
});

test('infaq page hides infaq content when off', function () {
    config(['finance.infaq_mode' => 'off']);

    $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.pindah-saldo'))
        ->assertOk()
        ->assertDontSee('Jadwal Infaq')
        ->assertDontSee('berinfaq');
});
