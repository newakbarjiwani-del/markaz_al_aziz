<?php

use App\Models\Tagihan;
use App\Services\Finance\Handlers\InquiryHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    $this->fixture = FinanceFixtures::schoolWithStudent();
    $this->admin = FinanceFixtures::adminUser();
});

test('existing paid tagihan keeps binary status when is_cicilan is false', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 500000,
        'paid' => 500000,
        'status' => Tagihan::STATUS_PAID,
        'paid_dt' => now(),
        'is_cicilan' => false,
    ]);

    expect($tagihan->isPaid())->toBeTrue()
        ->and($tagihan->statusLabel())->toBe('Lunas')
        ->and($tagihan->is_cicilan)->toBeFalse();
});

test('partial payment on non-cicilan tagihan stays unpaid', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 500000,
        'paid' => 100000,
        'is_cicilan' => false,
    ]);

    $tagihan->syncPaymentStatus();

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_UNPAID)
        ->and($tagihan->fresh()->paid_dt)->toBeNull();
});

test('admin can activate cicilan from tagihan update', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 300000,
        'due_date' => '2026-08-15',
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.keuangan.tagihan.update', $tagihan), [
            'amount' => 300000,
            'periode' => '2026-08',
            'due_date' => '2026-08-15',
            'enable_cicilan' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $tagihan->refresh();

    expect($tagihan->is_cicilan)->toBeTrue()
        ->and((float) $tagihan->total_amount)->toBe(300000.0)
        ->and((float) $tagihan->amount)->toBe(300000.0)
        ->and($tagihan->cicilanChildren()->count())->toBe(0);
});

test('student tagihan list exposes installment metadata for kasir form', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 250000,
        'is_cicilan' => true,
        'total_amount' => 250000,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.pembayaran.tagihan', $this->fixture->siswa))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.tagihan.0.id'))->toBe($tagihan->id)
        ->and($response->json('data.tagihan.0.is_cicilan'))->toBe(1)
        ->and($response->json('data.tagihan.0.can_pay'))->toBeTrue()
        ->and($response->json('data.tagihan.0.payable_amount'))->toBe(250000);
});

test('kasir can pay partial amount on installment bill', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 300000,
        'is_cicilan' => true,
        'total_amount' => 300000,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'items' => [
                ['tagihan_id' => $tagihan->id, 'amount' => 100000],
            ],
        ])
        ->assertCreated();

    expect((float) $response->json('data.total_amount'))->toBe(100000.0);

    $tagihan->refresh();
    $child = $tagihan->cicilanChildren()->first();

    expect($tagihan->status)->toBe(Tagihan::STATUS_CICILAN)
        ->and((float) $tagihan->paid)->toBe(100000.0)
        ->and((float) $tagihan->amount)->toBe(200000.0)
        ->and($child)->not->toBeNull()
        ->and($child->cicilan_ke)->toBe(1)
        ->and((float) $child->amount)->toBe(100000.0)
        ->and($child->isPaid())->toBeTrue();
});

test('va inquiry returns remaining cicilan amount', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 200000,
        'is_cicilan' => true,
        'total_amount' => 200000,
    ]);

    $response = app(InquiryHandler::class)->handle($this->fixture->siswa);
    $billMinor = (int) $response['BILL'];

    expect($response['ERR'])->toBe('00')
        ->and($billMinor)->toBe(200000 * 100);
});

test('full cicilan payments eventually mark parent tagihan lunas', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 120000,
        'is_cicilan' => true,
        'total_amount' => 120000,
    ]);

    foreach ([40000, 40000, 40000] as $slice) {
        $this->actingAs($this->admin)
            ->postJson(route('admin.keuangan.pembayaran.store'), [
                'fidbank' => '1140000',
                'items' => [
                    ['tagihan_id' => $tagihan->id, 'amount' => $slice],
                ],
            ])
            ->assertCreated();
        $tagihan->refresh();
    }

    expect($tagihan->status)->toBe(Tagihan::STATUS_PAID)
        ->and((float) $tagihan->paid)->toBe(120000.0)
        ->and((float) $tagihan->amount)->toBe(0.0)
        ->and($tagihan->cicilanChildren()->count())->toBe(3)
        ->and($tagihan->cicilanChildren()->where('status', Tagihan::STATUS_PAID)->count())->toBe(3);
});

test('cicilan cannot be cancelled after partial payment', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 150000,
        'is_cicilan' => true,
        'total_amount' => 150000,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'items' => [
                ['tagihan_id' => $tagihan->id, 'amount' => 50000],
            ],
        ])
        ->assertCreated();

    $tagihan->refresh();

    expect($tagihan->canCancelCicilan())->toBeFalse()
        ->and($tagihan->isCicilanInProgress())->toBeTrue();

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.tagihan.cicilan.show', $tagihan))
        ->assertOk()
        ->assertJsonPath('data.can_cancel', false);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.tagihan.cicilan.destroy', $tagihan))
        ->assertUnprocessable();

    $this->actingAs($this->admin)
        ->putJson(route('admin.keuangan.tagihan.update', $tagihan), [
            'amount' => 150000,
            'periode' => '2026-08',
            'due_date' => '2026-08-20',
            'enable_cicilan' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

test('cicilan tagihan without payments can be updated and deleted like regular bill', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 180000,
        'is_cicilan' => true,
        'total_amount' => 180000,
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.keuangan.tagihan.update', $tagihan), [
            'amount' => 200000,
            'periode' => '2026-08',
            'due_date' => '2026-08-20',
            'enable_cicilan' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $tagihan->refresh();

    expect((float) $tagihan->total_amount)->toBe(200000.0)
        ->and((float) $tagihan->amount)->toBe(200000.0);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.tagihan.destroy', $tagihan))
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('cicilan tagihan in progress cannot be updated or deleted', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 100000,
        'is_cicilan' => true,
        'total_amount' => 150000,
        'paid' => 50000,
        'status' => Tagihan::STATUS_CICILAN,
    ]);

    expect($tagihan->isBillingLocked())->toBeTrue();

    $this->actingAs($this->admin)
        ->putJson(route('admin.keuangan.tagihan.update', $tagihan), [
            'amount' => 180000,
            'periode' => '2026-08',
            'due_date' => '2026-08-20',
            'enable_cicilan' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tagihan cicilan yang sudah berjalan tidak dapat diubah.');

    $tagihan->refresh();

    expect((float) $tagihan->total_amount)->toBe(150000.0)
        ->and((float) $tagihan->amount)->toBe(100000.0)
        ->and((float) $tagihan->paid)->toBe(50000.0);

    $list = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.tagihan.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
        ->assertOk();

    $hasEdit = false;
    foreach ($list->json('data') as $row) {
        $action = $row[array_key_last($row)] ?? null;
        $raw = is_array($action) ? ($action['raw'] ?? null) : null;
        if (! is_array($raw) || ! isset($raw['actions']['edit'])) {
            continue;
        }

        $editUrl = $raw['actions']['edit']['update_url'] ?? '';
        if (str_contains($editUrl, '/tagihan/'.$tagihan->id)) {
            $hasEdit = true;
            break;
        }
    }

    expect($hasEdit)->toBeFalse();

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.tagihan.destroy', $tagihan))
        ->assertUnprocessable();
});

test('tagihan in cicilan progress cannot be deleted after payment', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 150000,
        'is_cicilan' => true,
        'total_amount' => 150000,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'items' => [
                ['tagihan_id' => $tagihan->id, 'amount' => 50000],
            ],
        ])
        ->assertCreated();

    $tagihan->refresh();

    expect($tagihan->status)->toBe(Tagihan::STATUS_CICILAN);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.keuangan.tagihan.destroy', $tagihan))
        ->assertUnprocessable();
});

test('installment child rows are excluded from root bill listing', function () {
    $parent = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 100000,
        'is_cicilan' => true,
        'total_amount' => 100000,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.store'), [
            'fidbank' => '1140000',
            'items' => [
                ['tagihan_id' => $parent->id, 'amount' => 50000],
            ],
        ])
        ->assertCreated();

    expect(Tagihan::rootBill()->count())->toBe(1)
        ->and(Tagihan::count())->toBe(2);
});

test('tagihan data can filter installment and non-installment bills', function () {
    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 100000,
        'is_cicilan' => true,
        'total_amount' => 100000,
    ]);

    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 120000,
        'jenis' => 'Uang Gedung',
        'is_cicilan' => false,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.tagihan.data', ['is_cicilan' => 1]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1);

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.tagihan.data', ['is_cicilan' => 0]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1);
});
