<?php

use App\Models\Tagihan;
use App\Models\JenisTagihan;
use App\Support\TagihanPeriode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
});

test('tagihan uses binary payment status', function () {
    $fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '1000001',
            'name' => 'Ahmad Test',
        ],
        'kelas' => [
            'name' => 'X IPA 1',
            'unit' => 'MA',
            'jenjang' => 'X',
        ],
    ]);

    $tagihan = FinanceFixtures::tagihan($fixture->siswa, $fixture->spp, $fixture->tahun, [
        'due_date' => now()->addDays(10),
    ]);

    expect($tagihan->isUnpaid())->toBeTrue()
        ->and($tagihan->statusLabel())->toBe('Belum Lunas');

    $tagihan->update(['paid' => 500000]);
    $paidAt = now()->subDay();
    $tagihan->syncPaymentStatus($paidAt);

    expect($tagihan->fresh()->isPaid())->toBeTrue()
        ->and($tagihan->fresh()->status)->toBe(Tagihan::STATUS_PAID)
        ->and($tagihan->fresh()->paid_dt?->toDateTimeString())->toBe($paidAt->toDateTimeString());
});

test('admin can update unpaid tagihan fields', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser();
    $tagihan = FinanceFixtures::tagihan($fixture->siswa, $fixture->spp, $fixture->tahun, [
        'amount' => 500000,
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'due_date' => '2026-07-15',
        'urutan' => 1,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.keuangan.tagihan.update', $tagihan), [
            'amount' => 550000,
            'periode' => '2026-08',
            'due_date' => '2026-08-20',
            'urutan' => 5,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $tagihan->refresh();

    expect((float) $tagihan->amount)->toBe(550000.0)
        ->and($tagihan->periode)->toBe(TagihanPeriode::fromCalendarMonth(2026, 8))
        ->and($tagihan->due_date?->toDateString())->toBe('2026-08-20')
        ->and($tagihan->urutan)->toBe(5);

    $this->assertDatabaseHas('log_tagihan_edit', [
        'tagihan_id' => $tagihan->id,
        'user_id' => $admin->id,
        'field' => 'amount',
        'old_value' => '500000',
        'new_value' => '550000',
    ]);
    $this->assertDatabaseHas('log_tagihan_edit', [
        'tagihan_id' => $tagihan->id,
        'user_id' => $admin->id,
        'field' => 'periode',
        'old_value' => (string) TagihanPeriode::fromCalendarMonth(2026, 7),
        'new_value' => (string) TagihanPeriode::fromCalendarMonth(2026, 8),
    ]);
});

test('admin cannot update paid tagihan', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser();
    $tagihan = FinanceFixtures::tagihan($fixture->siswa, $fixture->spp, $fixture->tahun, [
        'amount' => 500000,
        'paid' => 500000,
        'status' => Tagihan::STATUS_PAID,
        'paid_dt' => now(),
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.keuangan.tagihan.update', $tagihan), [
            'amount' => 600000,
            'periode' => '2026-08',
            'due_date' => '2026-08-20',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('tagihan data search matches student name without sql error', function () {
    $fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '1000099',
            'name' => 'Budi Pencarian',
        ],
    ]);
    $admin = FinanceFixtures::adminUser();
    FinanceFixtures::tagihan($fixture->siswa, $fixture->spp, $fixture->tahun);

    $this->actingAs($admin)
        ->getJson(route('admin.keuangan.tagihan.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Budi'],
        ]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1);
});

test('update tagihan can clear urutan', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser();
    $tagihan = FinanceFixtures::tagihan($fixture->siswa, $fixture->spp, $fixture->tahun, [
        'urutan' => 3,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.keuangan.tagihan.update', $tagihan), [
            'amount' => 500000,
            'periode' => TagihanPeriode::toMonthInput($tagihan->periode),
            'due_date' => '2026-07-15',
            'urutan' => null,
        ])
        ->assertOk();

    expect($tagihan->fresh()->urutan)->toBeNull();

    $this->assertDatabaseHas('log_tagihan_edit', [
        'tagihan_id' => $tagihan->id,
        'user_id' => $admin->id,
        'field' => 'urutan',
        'old_value' => '3',
        'new_value' => null,
    ]);
});

test('update tagihan without changes does not create edit log', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser();
    $tagihan = FinanceFixtures::tagihan($fixture->siswa, $fixture->spp, $fixture->tahun, [
        'amount' => 500000,
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'due_date' => '2026-07-15',
        'urutan' => 2,
    ]);

    $this->actingAs($admin)
        ->putJson(route('admin.keuangan.tagihan.update', $tagihan), [
            'amount' => 500000,
            'periode' => '2026-07',
            'due_date' => '2026-07-15',
            'urutan' => 2,
        ])
        ->assertOk();

    expect(\App\Models\LogTagihanEdit::query()->where('tagihan_id', $tagihan->id)->count())->toBe(0);
});

test('store non-spp tagihan can omit periode and defaults to current month', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser();
    $nonSpp = JenisTagihan::create([
        'name' => 'Uang Gedung',
        'default_amount' => 700000,
        'is_spp' => false,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.keuangan.tagihan.store'), [
            'siswa_id' => $fixture->siswa->id,
            'tahun_akademik_id' => $fixture->tahun->id,
            'jenis_tagihan_id' => $nonSpp->id,
            'amount' => 700000,
        ])
        ->assertCreated();

    $created = Tagihan::query()->where('jenis_tagihan_id', $nonSpp->id)->latest('id')->first();
    expect($created)->not->toBeNull()
        ->and($created->periode)->toBe(TagihanPeriode::fromCalendarMonth((int) now()->year, (int) now()->month));
});

test('store spp tagihan still requires periode', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser();

    $this->actingAs($admin)
        ->postJson(route('admin.keuangan.tagihan.store'), [
            'siswa_id' => $fixture->siswa->id,
            'tahun_akademik_id' => $fixture->tahun->id,
            'jenis_tagihan_id' => $fixture->spp->id,
            'amount' => 500000,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['periode']);
});
