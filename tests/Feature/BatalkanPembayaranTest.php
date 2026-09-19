<?php

use App\Models\Pembayaran;
use App\Models\SaldoKeuangan;
use App\Models\Sccttran;
use App\Models\Tagihan;
use App\Models\User;
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

test('batalkan pembayaran page lists kasir payments', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-CANCEL-LIST',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.batalkan-pembayaran.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);

    $actionCell = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.batalkan-pembayaran.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))
        ->json('data.0.8');

    expect($actionCell['display'])->toContain('data-payment-detail')
        ->and($actionCell['display'])->toContain('data-fetch-delete');
});

test('admin can cancel tunai payment and restore unpaid bill', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 250000,
    ]);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-CANCEL-TUNAI',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $paymentId = $store->json('data.payment_id');

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.batalkan-pembayaran.destroy', $paymentId))
        ->assertOk()
        ->assertJsonPath('success', true);

    $tagihan->refresh();

    expect($tagihan->isPaid())->toBeFalse()
        ->and((float) $tagihan->paid)->toBe(0.0)
        ->and(Pembayaran::count())->toBe(0)
        ->and(Pembayaran::withTrashed()->count())->toBe(1);

    $this->assertDatabaseHas('log_pembayaran_batal', [
        'pembayaran_id' => $paymentId,
        'siswa_id' => $this->fixture->siswa->id,
        'cancelled_by' => $this->admin->id,
        'reference' => 'KW-CANCEL-TUNAI',
        'method' => '1140000',
    ]);
});

test('admin can cancel installment payment and restore parent bill', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 300000,
        'is_cicilan' => true,
        'total_amount' => 300000,
    ]);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-CANCEL-CICILAN',
            'items' => [
                ['tagihan_id' => $tagihan->id, 'amount' => 100000],
            ],
        ])
        ->assertCreated();

    $paymentId = $store->json('data.payment_id');
    $tagihan->refresh();

    expect($tagihan->status)->toBe(Tagihan::STATUS_CICILAN)
        ->and((float) $tagihan->amount)->toBe(200000.0)
        ->and((float) $tagihan->paid)->toBe(100000.0)
        ->and($tagihan->cicilanChildren()->count())->toBe(1);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.batalkan-pembayaran.destroy', $paymentId))
        ->assertOk();

    $tagihan->refresh();

    expect($tagihan->status)->toBe(Tagihan::STATUS_UNPAID)
        ->and((float) $tagihan->amount)->toBe(300000.0)
        ->and((float) $tagihan->paid)->toBe(0.0)
        ->and($tagihan->cicilanChildren()->count())->toBe(0)
        ->and(Tagihan::onlyTrashed()->where('parent_id', $tagihan->id)->count())->toBe(1);
});

test('admin can cancel saldo keuangan payment and credit balance back', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 200000,
    ]);

    SaldoKeuangan::create([
        'siswa_id' => $this->fixture->siswa->id,
        'balance' => 500000,
    ]);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140002',
            'reference' => 'SK-CANCEL-01',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $paymentId = $store->json('data.payment_id');
    $payment = Pembayaran::with('details')->find($paymentId);
    $originalSccttranId = $payment->details->first()->sccttran_id;

    expect((float) SaldoKeuangan::first()->balance)->toBe(300000.0)
        ->and(Sccttran::where('METODE', 'FROM SALDO')->count())->toBe(1);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.batalkan-pembayaran.destroy', $paymentId))
        ->assertOk();

    expect((float) SaldoKeuangan::first()->balance)->toBe(500000.0)
        ->and($tagihan->fresh()->isPaid())->toBeFalse()
        ->and(Sccttran::whereKey($originalSccttranId)->exists())->toBeTrue()
        ->and(Sccttran::where('METODE', 'FROM SALDO')->count())->toBe(1)
        ->and(Sccttran::where('METODE', 'JURNAL SALDO')->where('NOREFF', 'VOID-SK-CANCEL-01')->count())->toBe(1);
});

test('cancel payment log page lists cancelled payments', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-LOG-LIST',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.batalkan-pembayaran.destroy', $store->json('data.payment_id')))
        ->assertOk();

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.log-batalkan-pembayaran.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});

test('cancel payment log detail returns snapshot', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 150000,
    ]);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-LOG-DETAIL',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.batalkan-pembayaran.destroy', $store->json('data.payment_id')))
        ->assertOk();

    $logId = \App\Models\LogPembayaranBatal::query()->value('id');

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.log-batalkan-pembayaran.show', $logId))
        ->assertOk()
        ->assertJsonPath('data.reference', 'KW-LOG-DETAIL')
        ->assertJsonPath('data.items.0.amount', 150000)
        ->assertJsonPath('data.cancelled_by.username', $this->admin->username);
});

test('cancel payment requires finance delete permission', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun);

    $store = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'reference' => 'KW-CANCEL-AUTH',
            'items' => [
                ['tagihan_id' => $tagihan->id],
            ],
        ])
        ->assertCreated();

    $viewer = User::create([
        'username' => 'finance.viewer',
        'name' => 'Finance Viewer',
        'email' => 'finance-viewer@local.test',
        'password' => bcrypt('password'),
        'status' => 'aktif',
    ]);
    $viewer->givePermissionTo('finance.view');

    $this->actingAs($viewer)
        ->deleteJson(route('admin.keuangan.batalkan-pembayaran.destroy', $store->json('data.payment_id')))
        ->assertForbidden();
});
