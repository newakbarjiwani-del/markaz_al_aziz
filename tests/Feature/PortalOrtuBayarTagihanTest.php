<?php

use App\Models\OrangTua;
use App\Models\Pembayaran;
use App\Models\SaldoKeuangan;
use App\Models\Sccttran;
use App\Models\Tagihan;
use App\Models\User;
use App\Support\TagihanPeriode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '1000401',
            'name' => 'Siswa Ortu Bayar',
        ],
    ]);

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'nama_ayah' => 'Bapak Bayar',
        'nama_ibu' => 'Ibu Bayar',
        'status' => 'aktif',
    ]);
    $this->orangTua->siswa()->attach($this->fixture->siswa->id);

    $this->ortuUser = User::create([
        'username' => 'ortu.bayar',
        'name' => $this->orangTua->displayName(),
        'email' => 'ortu-bayar@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'orang_tua_id' => $this->orangTua->id,
    ]);
    $this->ortuUser->assignRole('orang_tua');
});

test('parent can view unpaid child bills with saldo', function () {
    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 300000,
    ]);

    SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 500000,
    ]);

    $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.tagihan.unpaid', [
            'siswa_id' => $this->fixture->siswa->id,
        ]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.saldo', 500000)
        ->assertJsonPath('data.tagihan.0.remaining', 300000);
});

test('parent can pay unpaid child bills using saldo', function () {
    $tagihanA = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 200000,
    ]);
    $tagihanB = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 8),
        'amount' => 150000,
    ]);

    $saldo = SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 500000,
    ]);

    $response = $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.tagihan.bayar'), [
            'siswa_id' => $this->fixture->siswa->id,
            'items' => [
                ['tagihan_id' => $tagihanA->id],
                ['tagihan_id' => $tagihanB->id],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.processed', 2);

    expect((float) $response->json('data.total_amount'))->toBe(350000.0);
    expect($tagihanA->fresh()->isPaid())->toBeTrue();
    expect($tagihanB->fresh()->isPaid())->toBeTrue();
    expect((float) $saldo->fresh()->balance)->toBe(150000.0);
    expect(Pembayaran::count())->toBe(1);
    expect(Pembayaran::first()->method)->toBe('1140002');
    expect(Pembayaran::first()->user_id)->toBe($this->ortuUser->id);
    expect(Sccttran::where('METODE', 'FROM SALDO')->count())->toBe(2);
});

test('parent cannot pay when saldo is insufficient', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 400000,
    ]);

    SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 100000,
    ]);

    $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.tagihan.bayar'), [
            'siswa_id' => $this->fixture->siswa->id,
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($tagihan->fresh()->isPaid())->toBeFalse();
});

test('parent cannot pay bills for unlinked child', function () {
    $other = FinanceFixtures::schoolWithStudent([
        'sekolah' => ['code' => 'mts', 'name' => 'MTs Other'],
        'siswa' => ['nis' => '2000999', 'name' => 'Anak Lain'],
        'spp' => ['name' => 'SPP Other'],
    ]);

    $tagihan = FinanceFixtures::tagihan($other->siswa, $other->spp, $other->tahun, [
        'amount' => 100000,
    ]);

    SaldoKeuangan::create([
        'siswa_id' => $other->siswa->id,
        'balance' => 500000,
    ]);

    $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.tagihan.bayar'), [
            'siswa_id' => $other->siswa->id,
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertForbidden();
});

test('parent tagihan page shows status and jenis filters', function () {
    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);

    $seragam = FinanceFixtures::jenisTagihan($this->fixture->sekolah, [
        'name' => 'Seragam',
        'is_spp' => false,
    ]);
    FinanceFixtures::tagihan($this->fixture->siswa, $seragam, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 8),
    ]);

    $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.tagihan.index'))
        ->assertOk()
        ->assertSee('filter-status', false)
        ->assertSee('filter-jenis', false)
        ->assertSee('Jenis Tagihan', false)
        ->assertSee('Seragam', false);
});

test('parent tagihan data can filter by status and jenis', function () {
    $sppUnpaid = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'status' => Tagihan::STATUS_UNPAID,
    ]);

    $seragam = FinanceFixtures::jenisTagihan($this->fixture->sekolah, [
        'name' => 'Seragam',
        'is_spp' => false,
    ]);
    $seragamPaid = FinanceFixtures::tagihan($this->fixture->siswa, $seragam, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 8),
        'status' => Tagihan::STATUS_PAID,
        'paid' => 250000,
        'amount' => 250000,
    ]);

    $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.tagihan.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'status' => '1',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);

    $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.tagihan.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'jenis' => 'SPP',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);

    $sppResponse = $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.tagihan.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'jenis' => 'SPP',
        ]))
        ->assertOk();

    expect(collect($sppResponse->json('data'))->first()[4])->toBe('SPP');
});
