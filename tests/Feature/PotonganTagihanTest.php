<?php

use App\Models\JenisPotongan;
use App\Models\JenisTagihan;
use App\Models\PotonganPemakaian;
use App\Models\PotonganSiswa;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\Finance\PotonganTagihanService;
use App\Support\PotonganSiswaStatus;
use App\Support\PotonganTipe;
use App\Support\TagihanPeriode;
use Database\Seeders\PotonganCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    (new PotonganCatalogSeeder)->run();
});

function potonganFixture(): object
{
    $ctx = FinanceFixtures::schoolWithStudent();

    $beasiswa = JenisPotongan::query()->where('nama', 'Beasiswa')->firstOrFail();
    $kurangMampu = JenisPotongan::query()->where('nama', 'Kurang Mampu')->firstOrFail();

    return (object) [
        ...((array) $ctx),
        'beasiswa' => $beasiswa,
        'kurangMampu' => $kurangMampu,
    ];
}

function createPotonganAssignment(
    object $ctx,
    JenisPotongan $jenis,
    string $tipe,
    int $nilai,
    int $maxPemakaian = 12,
): PotonganSiswa {
    return PotonganSiswa::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'jenis_potongan_id' => $jenis->id,
        'tipe' => $tipe,
        'nilai' => $nilai,
        'berlaku_mulai' => now()->subMonth()->toDateString(),
        'berlaku_sampai' => now()->addYear()->toDateString(),
        'max_pemakaian' => $maxPemakaian,
        'status' => PotonganSiswaStatus::ACTIVE,
    ]);
}

function createUnpaidTagihan(object $ctx, int $amount = 500000): Tagihan
{
    return Tagihan::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $ctx->spp->id,
        'jenis' => $ctx->spp->name,
        'amount' => $amount,
        'periode' => TagihanPeriode::current(),
        'paid' => 0,
        'status' => Tagihan::STATUS_UNPAID,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);
}

test('percent potongan applies on remaining sisa', function () {
    $ctx = potonganFixture();
    createPotonganAssignment($ctx, $ctx->beasiswa, PotonganTipe::PERCENT, 50);
    $tagihan = createUnpaidTagihan($ctx, 500000);

    app(PotonganTagihanService::class)->autoApply($tagihan->fresh(['siswa']));

    $tagihan->refresh();
    expect((int) $tagihan->amount_bruto)->toBe(500000)
        ->and((int) $tagihan->potongan_amount)->toBe(250000)
        ->and((int) $tagihan->amount)->toBe(250000);
});

test('stacked beasiswa and kurang mampu follow sort order', function () {
    $ctx = potonganFixture();
    createPotonganAssignment($ctx, $ctx->beasiswa, PotonganTipe::PERCENT, 50);
    createPotonganAssignment($ctx, $ctx->kurangMampu, PotonganTipe::FIXED, 100000);
    $tagihan = createUnpaidTagihan($ctx, 500000);

    app(PotonganTagihanService::class)->autoApply($tagihan->fresh(['siswa']));

    $tagihan->refresh();
    expect((int) $tagihan->potongan_amount)->toBe(350000)
        ->and((int) $tagihan->amount)->toBe(150000)
        ->and(PotonganPemakaian::where('tagihan_id', $tagihan->id)->count())->toBe(2);
});

test('auto apply runs on tagihan store', function () {
    $ctx = potonganFixture();
    createPotonganAssignment($ctx, $ctx->beasiswa, PotonganTipe::PERCENT, 50);
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $ctx->sekolah->id]);

    $response = $this->actingAs($admin)->postJson(route('admin.keuangan.tagihan.store'), [
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $ctx->spp->id,
        'amount' => 500000,
        'periode' => TagihanPeriode::toMonthInput(TagihanPeriode::current()),
    ]);

    $response->assertCreated();
    $tagihan = Tagihan::query()->latest('id')->first();
    expect((int) $tagihan->potongan_amount)->toBe(250000)
        ->and((int) $tagihan->amount)->toBe(250000);
});

test('manual apply rejects paid tagihan and already applied', function () {
    $ctx = potonganFixture();
    createPotonganAssignment($ctx, $ctx->beasiswa, PotonganTipe::PERCENT, 50);
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $ctx->sekolah->id]);
    $tagihan = createUnpaidTagihan($ctx);

    app(PotonganTagihanService::class)->autoApply($tagihan->fresh(['siswa']));

    $this->actingAs($admin)
        ->postJson(route('admin.keuangan.tagihan.potongan.apply', $tagihan))
        ->assertStatus(422);

    $paid = createUnpaidTagihan($ctx, 300000);
    $paid->update(['status' => Tagihan::STATUS_PAID, 'paid' => 300000]);

    $this->actingAs($admin)
        ->postJson(route('admin.keuangan.tagihan.potongan.apply', $paid))
        ->assertStatus(422);
});

test('manual apply on existing unpaid tagihan succeeds', function () {
    $ctx = potonganFixture();
    createPotonganAssignment($ctx, $ctx->kurangMampu, PotonganTipe::FIXED, 100000);
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $ctx->sekolah->id]);
    $tagihan = createUnpaidTagihan($ctx, 500000);

    $this->actingAs($admin)
        ->postJson(route('admin.keuangan.tagihan.potongan.apply', $tagihan))
        ->assertOk();

    $tagihan->refresh();
    expect((int) $tagihan->potongan_amount)->toBe(100000)
        ->and((int) $tagihan->amount)->toBe(400000);
});

test('expired assignment is not eligible', function () {
    $ctx = potonganFixture();
    $assignment = createPotonganAssignment($ctx, $ctx->beasiswa, PotonganTipe::PERCENT, 50);
    $assignment->update(['berlaku_sampai' => now()->subDay()->toDateString()]);
    $tagihan = createUnpaidTagihan($ctx);

    $applied = app(PotonganTagihanService::class)->autoApply($tagihan->fresh(['siswa']));

    expect($applied)->toBeFalse()
        ->and((float) $tagihan->fresh()->potongan_amount)->toBe(0.0);
});

test('katalog potongan seeder is idempotent', function () {
    (new PotonganCatalogSeeder)->run();
    expect(JenisPotongan::count())->toBe(2);
});

test('per bill custom cut uses pivot tipe and nilai', function () {
    $ctx = potonganFixture();
    $seragam = JenisTagihan::query()->firstOrCreate(
        ['name' => 'Seragam'],
        [
            'code' => 'seragam',
            'default_amount' => 200000,
            'sort_order' => 3,
            'is_active' => true,
        ]
    );

    $assignment = PotonganSiswa::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'jenis_potongan_id' => $ctx->beasiswa->id,
        'tipe' => PotonganTipe::PERCENT,
        'nilai' => 50,
        'berlaku_mulai' => now()->subMonth()->toDateString(),
        'berlaku_sampai' => now()->addYear()->toDateString(),
        'max_pemakaian' => 12,
        'status' => PotonganSiswaStatus::ACTIVE,
    ]);

    $assignment->jenisTagihan()->sync([
        $ctx->spp->id => ['tipe' => null, 'nilai' => null, 'max_pemakaian' => null],
        $seragam->id => ['tipe' => PotonganTipe::FIXED, 'nilai' => 75000, 'max_pemakaian' => null],
    ]);

    $sppTagihan = Tagihan::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $ctx->spp->id,
        'jenis' => $ctx->spp->name,
        'amount' => 500000,
        'periode' => TagihanPeriode::current(),
        'paid' => 0,
        'status' => Tagihan::STATUS_UNPAID,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    $seragamTagihan = Tagihan::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $seragam->id,
        'jenis' => $seragam->name,
        'amount' => 200000,
        'periode' => TagihanPeriode::normalize('202602'),
        'paid' => 0,
        'status' => Tagihan::STATUS_UNPAID,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    app(PotonganTagihanService::class)->autoApply($sppTagihan->fresh(['siswa']));
    app(PotonganTagihanService::class)->autoApply($seragamTagihan->fresh(['siswa']));

    $sppTagihan->refresh();
    $seragamTagihan->refresh();

    expect((int) $sppTagihan->potongan_amount)->toBe(250000)
        ->and((int) $sppTagihan->amount)->toBe(250000)
        ->and((int) $seragamTagihan->potongan_amount)->toBe(75000)
        ->and((int) $seragamTagihan->amount)->toBe(125000);
});

test('pivot with null tipe nilai falls back to assignment default', function () {
    $ctx = potonganFixture();

    $assignment = PotonganSiswa::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'jenis_potongan_id' => $ctx->beasiswa->id,
        'tipe' => PotonganTipe::PERCENT,
        'nilai' => 40,
        'berlaku_mulai' => now()->subMonth()->toDateString(),
        'berlaku_sampai' => now()->addYear()->toDateString(),
        'max_pemakaian' => 12,
        'status' => PotonganSiswaStatus::ACTIVE,
    ]);

    $assignment->jenisTagihan()->sync([
        $ctx->spp->id => ['tipe' => null, 'nilai' => null, 'max_pemakaian' => null],
    ]);

    $tagihan = createUnpaidTagihan($ctx, 500000);

    app(PotonganTagihanService::class)->autoApply($tagihan->fresh(['siswa']));

    $tagihan->refresh();
    expect((int) $tagihan->potongan_amount)->toBe(200000)
        ->and((int) $tagihan->amount)->toBe(300000);
});

test('per jenis max pemakaian tracks quota separately per bill type', function () {
    $ctx = potonganFixture();
    $seragam = JenisTagihan::query()->firstOrCreate(
        ['name' => 'Seragam'],
        [
            'code' => 'seragam',
            'default_amount' => 200000,
            'sort_order' => 3,
            'is_active' => true,
        ]
    );

    $assignment = PotonganSiswa::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'jenis_potongan_id' => $ctx->beasiswa->id,
        'tipe' => PotonganTipe::PERCENT,
        'nilai' => 50,
        'berlaku_mulai' => now()->subMonth()->toDateString(),
        'berlaku_sampai' => now()->addYear()->toDateString(),
        'max_pemakaian' => 12,
        'status' => PotonganSiswaStatus::ACTIVE,
    ]);

    $assignment->jenisTagihan()->sync([
        $ctx->spp->id => ['tipe' => null, 'nilai' => null, 'max_pemakaian' => 1],
        $seragam->id => ['tipe' => null, 'nilai' => null, 'max_pemakaian' => 2],
    ]);

    $service = app(PotonganTagihanService::class);

    $sppOne = Tagihan::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $ctx->spp->id,
        'jenis' => $ctx->spp->name,
        'amount' => 500000,
        'periode' => TagihanPeriode::current(),
        'paid' => 0,
        'status' => Tagihan::STATUS_UNPAID,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    $sppTwo = Tagihan::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $ctx->spp->id,
        'jenis' => $ctx->spp->name,
        'amount' => 500000,
        'periode' => TagihanPeriode::normalize('202602'),
        'paid' => 0,
        'status' => Tagihan::STATUS_UNPAID,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    $seragamOne = Tagihan::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $seragam->id,
        'jenis' => $seragam->name,
        'amount' => 200000,
        'periode' => TagihanPeriode::normalize('202603'),
        'paid' => 0,
        'status' => Tagihan::STATUS_UNPAID,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    $seragamTwo = Tagihan::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $seragam->id,
        'jenis' => $seragam->name,
        'amount' => 200000,
        'periode' => TagihanPeriode::normalize('202604'),
        'paid' => 0,
        'status' => Tagihan::STATUS_UNPAID,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    $seragamThree = Tagihan::create([
        'sekolah_id' => $ctx->sekolah->id,
        'siswa_id' => $ctx->siswa->id,
        'tahun_akademik_id' => $ctx->tahun->id,
        'jenis_tagihan_id' => $seragam->id,
        'jenis' => $seragam->name,
        'amount' => 200000,
        'periode' => TagihanPeriode::normalize('202605'),
        'paid' => 0,
        'status' => Tagihan::STATUS_UNPAID,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    expect($service->autoApply($sppOne->fresh(['siswa'])))->toBeTrue();
    expect($service->autoApply($sppTwo->fresh(['siswa'])))->toBeFalse();
    expect($service->autoApply($seragamOne->fresh(['siswa'])))->toBeTrue();
    expect($service->autoApply($seragamTwo->fresh(['siswa'])))->toBeTrue();
    expect($service->autoApply($seragamThree->fresh(['siswa'])))->toBeFalse();

    $sppOne->refresh();
    $sppTwo->refresh();
    $seragamOne->refresh();
    $seragamTwo->refresh();
    $seragamThree->refresh();

    expect((int) $sppOne->potongan_amount)->toBe(250000)
        ->and((float) $sppTwo->potongan_amount)->toBe(0.0)
        ->and((int) $seragamOne->potongan_amount)->toBe(100000)
        ->and((int) $seragamTwo->potongan_amount)->toBe(100000)
        ->and((float) $seragamThree->potongan_amount)->toBe(0.0)
        ->and($assignment->fresh()->status)->toBe(PotonganSiswaStatus::EXHAUSTED);
});

test('100 percent potongan waives the bill to zero', function () {
    $ctx = potonganFixture();
    createPotonganAssignment($ctx, $ctx->beasiswa, PotonganTipe::PERCENT, 100);
    $tagihan = createUnpaidTagihan($ctx, 500000);

    app(PotonganTagihanService::class)->autoApply($tagihan->fresh(['siswa']));

    $tagihan->refresh();
    expect((int) $tagihan->amount_bruto)->toBe(500000)
        ->and((int) $tagihan->potongan_amount)->toBe(500000)
        ->and((int) $tagihan->amount)->toBe(0)
        ->and($tagihan->remaining())->toBe(0.0);
});

test('percent nilai above 100 is rejected and formatted currency is accepted', function () {
    $ctx = potonganFixture();
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $ctx->sekolah->id]);

    $payload = [
        'siswa_id' => $ctx->siswa->id,
        'jenis_potongan_id' => $ctx->beasiswa->id,
        'tipe' => PotonganTipe::PERCENT,
        'nilai' => 150,
        'max_pemakaian' => 12,
        'berlaku_mulai' => now()->toDateString(),
        'berlaku_sampai' => now()->addYear()->toDateString(),
        'status' => PotonganSiswaStatus::ACTIVE,
        'bill_cuts' => [
            [
                'jenis_tagihan_id' => $ctx->spp->id,
                'enabled' => 1,
                'use_default' => 1,
                'use_default_max' => 1,
            ],
        ],
    ];

    $this->actingAs($admin)
        ->postJson(route('admin.keuangan.potongan-siswa.store'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['nilai']);

    $payload['tipe'] = PotonganTipe::FIXED;
    $payload['nilai'] = '100.000';

    $this->actingAs($admin)
        ->postJson(route('admin.keuangan.potongan-siswa.store'), $payload)
        ->assertCreated();

    $assignment = PotonganSiswa::query()->latest('id')->first();
    expect((int) $assignment->nilai)->toBe(100000)
        ->and($assignment->tipe)->toBe(PotonganTipe::FIXED);
});
