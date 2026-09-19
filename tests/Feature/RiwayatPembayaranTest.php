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

test('riwayat pembayaran lists one receipt per payment header', function () {
    $tagihanA = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);
    $tagihanB = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 8),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-GROUP-01',
            'paid_dt' => '2026-07-06 11:00:00',
            'items' => [
                ['tagihan_id' => $tagihanA->id],
                ['tagihan_id' => $tagihanB->id],
            ],
        ])
        ->assertCreated();

    expect(Pembayaran::count())->toBe(1);
    expect(PembayaranDetail::count())->toBe(2);
    expect(Tagihan::paid()->count())->toBe(2);
    expect($tagihanA->fresh()->reference)->toBe('KW-GROUP-01');
    expect($tagihanB->fresh()->reference)->toBe('KW-GROUP-01');
});

test('riwayat pembayaran filters by student search and reference', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140003',
            'reference' => 'KW-FILTER-01',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $payment = Pembayaran::first();
    expect($payment->reference)->toBe('KW-FILTER-01');
    expect($payment->method)->toBe('1140003');
});

test('riwayat pembayaran receipt endpoint returns payment detail list', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-SINGLE-01',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $paymentId = $store->json('data.payment_id');

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.riwayat-pembayaran.receipt', [
            'ids' => [$paymentId],
        ]))
        ->assertOk()
        ->assertJsonPath('data.reference', 'KW-SINGLE-01')
        ->assertJsonPath('data.operator', 'Admin Test')
        ->assertJsonPath('data.items.0.jenis', 'SPP');
});

test('riwayat endpoint returns datatable rows for payments', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-DT-01',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.riwayat-pembayaran.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);

    $actionCell = $response->json('data.0.8');
    expect($actionCell['display'])->toContain('data-payment-detail')
        ->and($actionCell['display'])->toContain('data-print-payment-receipt');
});

test('riwayat pembayaran receipt includes bill lines for detail modal', function () {
    $tagihanA = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);
    $tagihanB = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 8),
        'jenis' => 'Uang Gedung',
    ]);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-DETAIL-01',
            'items' => [
                ['tagihan_id' => $tagihanA->id],
                ['tagihan_id' => $tagihanB->id],
            ],
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.riwayat-pembayaran.receipt', [
            'ids' => [$store->json('data.payment_id')],
        ]))
        ->assertOk()
        ->assertJsonPath('data.item_count', 2)
        ->assertJsonCount(2, 'data.items')
        ->assertJsonPath('data.items.0.jenis', 'SPP')
        ->assertJsonPath('data.items.1.jenis', 'Uang Gedung')
        ->assertJsonPath('data.items.0.bill_amount', (int) (float) $tagihanA->amount)
        ->assertJsonPath('data.items.0.bill_amount_label', 'Rp '.number_format((float) $tagihanA->amount, 0, ',', '.'))
        ->assertJsonPath('data.total_amount', (int) ((float) $tagihanA->amount + (float) $tagihanB->amount))
        ->assertJsonPath('data.total_label', 'Rp '.number_format((float) $tagihanA->amount + (float) $tagihanB->amount, 0, ',', '.'));
});

test('riwayat pembayaran receipt shows original bill total for cicilan partial pay', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 300000,
        'is_cicilan' => true,
        'total_amount' => 300000,
    ]);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-DETAIL-CICILAN',
            'items' => [
                ['tagihan_id' => $tagihan->id, 'amount' => 100000],
            ],
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.riwayat-pembayaran.receipt', [
            'ids' => [$store->json('data.payment_id')],
        ]))
        ->assertOk()
        ->assertJsonPath('data.items.0.amount', 100000)
        ->assertJsonPath('data.items.0.bill_amount', 300000)
        ->assertJsonPath('data.items.0.paid_total', 100000)
        ->assertJsonPath('data.items.0.is_cicilan', true);
});

test('riwayat pembayaran receipt paid total accumulates across cicilan payments', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 300000,
        'is_cicilan' => true,
        'total_amount' => 300000,
    ]);

    $first = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-CICIL-1',
            'items' => [
                ['tagihan_id' => $tagihan->id, 'amount' => 100000],
            ],
        ])
        ->assertCreated();

    $second = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-CICIL-2',
            'items' => [
                ['tagihan_id' => $tagihan->id, 'amount' => 75000],
            ],
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.riwayat-pembayaran.receipt', [
            'ids' => [$first->json('data.payment_id')],
        ]))
        ->assertOk()
        ->assertJsonPath('data.items.0.bill_amount', 300000)
        ->assertJsonPath('data.items.0.amount', 100000)
        ->assertJsonPath('data.items.0.paid_total', 100000);

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.riwayat-pembayaran.receipt', [
            'ids' => [$second->json('data.payment_id')],
        ]))
        ->assertOk()
        ->assertJsonPath('data.items.0.bill_amount', 300000)
        ->assertJsonPath('data.items.0.amount', 75000)
        ->assertJsonPath('data.items.0.paid_total', 175000);
});
