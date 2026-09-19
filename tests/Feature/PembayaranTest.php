<?php

use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Tagihan;
use App\Support\TagihanPeriode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '1000101',
            'name' => 'Siswa Bayar',
        ],
    ]);
    $this->admin = FinanceFixtures::adminUser();
});

test('admin can store bulk payment as payment header with details', function () {
    $tagihanA = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 500000,
    ]);
    $tagihanB = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 8),
        'amount' => 500000,
        'jenis' => 'Uang Gedung',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-TEST-001',
            'paid_dt' => '2026-07-06 10:00:00',
            'items' => [
                ['tagihan_id' => $tagihanA->id],
                ['tagihan_id' => $tagihanB->id],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    expect($response->json('data.processed'))->toBe(2)
        ->and($response->json('data.receipt.reference'))->toBe('KW-TEST-001')
        ->and($response->json('data.receipt.item_count'))->toBe(2)
        ->and($response->json('data.receipt.operator'))->toBe('Admin Test')
        ->and((float) $response->json('data.receipt.total_amount'))->toBe(1000000.0);

    expect(Pembayaran::count())->toBe(1);
    expect(PembayaranDetail::count())->toBe(2);

    $payment = Pembayaran::first();
    expect($payment->siswa_id)->toBe($this->fixture->siswa->id)
        ->and($payment->user_id)->toBe($this->admin->id)
        ->and($payment->method)->toBe('1140000')
        ->and($payment->reference)->toBe('KW-TEST-001')
        ->and((float) $payment->total_amount)->toBe(1000000.0);

    expect($tagihanA->fresh()->isPaid())->toBeTrue();
    expect($tagihanB->fresh()->isPaid())->toBeTrue();

    expect($tagihanA->fresh()->fidbank)->toBe('1140000');
    expect($tagihanA->fresh()->reference)->toBe('KW-TEST-001');
    expect($tagihanA->fresh()->user_id)->toBe($this->admin->id);

    expect($tagihanB->fresh()->fidbank)->toBe('1140000');
    expect($tagihanB->fresh()->reference)->toBe('KW-TEST-001');
    expect($tagihanB->fresh()->user_id)->toBe($this->admin->id);
});

test('cannot pay already paid tagihan', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'paid' => 500000,
        'status' => Tagihan::STATUS_PAID,
        'paid_dt' => now(),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('manual saldo payment debits saldo_keuangan', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 300000,
    ]);

    $saldoKeuangan = \App\Models\SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 500000,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140002',
            'reference' => 'SALDO-TEST-001',
            'paid_dt' => '2026-07-06 10:00:00',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    expect($response->json('data.processed'))->toBe(1);

    $tagihan->fresh();
    expect($tagihan->fresh()->isPaid())->toBeTrue();

    $payment = Pembayaran::first();
    expect($payment->method)->toBe('1140002');
    expect($payment->details()->count())->toBe(1);
    expect($payment->details->first()->sccttran_id)->not->toBeNull();

    $saldoKeuangan->fresh();
    expect((float) $saldoKeuangan->fresh()->balance)->toBe(200000.0);

    expect(\App\Models\Sccttran::where('METODE', 'FROM SALDO')->count())->toBe(1);
});

test('tunai payment auto generates KW reference when empty', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 200000,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $reference = $response->json('data.receipt.reference');

    expect($reference)->toMatch('/^KW-\d{8}-[A-Z0-9]{6}$/')
        ->and(Pembayaran::first()->reference)->toBe($reference);
});

test('saldo payment auto generates SK reference when empty', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 200000,
    ]);

    \App\Models\SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 500000,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140002',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $reference = $response->json('data.receipt.reference');

    expect($reference)->toMatch('/^SK-\d{8}-[A-Z0-9]{6}$/')
        ->and(Pembayaran::first()->reference)->toBe($reference);
});

test('manual saldo fails when insufficient balance', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 500000,
    ]);

    \App\Models\SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 100000,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140002',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});
