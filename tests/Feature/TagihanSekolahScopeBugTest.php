<?php

use App\Models\JenisTagihan;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Support\TagihanPeriode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
});

test('scoped admin can store tagihan without sending sekolah_id', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $fixture->sekolah->id]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.keuangan.tagihan.store'), [
            'siswa_id' => $fixture->siswa->id,
            'tahun_akademik_id' => $fixture->tahun->id,
            'jenis_tagihan_id' => $fixture->spp->id,
            'amount' => 500000,
            'periode' => '2026-01',
        ]);

    $response->assertCreated()->assertJsonPath('success', true);
    expect($response->json('errors'))->toBeNull();
    expect(Tagihan::query()->where('siswa_id', $fixture->siswa->id)->exists())->toBeTrue();
});

test('scoped admin can generate tagihan without sending sekolah_id', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $fixture->sekolah->id]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.keuangan.tagihan.generate'), [
            'tahun_akademik_id' => $fixture->tahun->id,
            'jenis_tagihan_id' => $fixture->spp->id,
            'periode' => '2026-07',
            'amount' => 500000,
        ]);

    $response->assertOk()->assertJsonPath('success', true);
});

test('scoped admin can create tahun akademik and jenis tagihan without sekolah_id', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $fixture->sekolah->id]);

    $this->actingAs($admin)
        ->postJson(route('admin.master-data.tahun-akademik.store'), [
            'name' => '2026/2027',
            'is_active' => '0',
        ])
        ->assertCreated()
        ->assertJsonMissingValidationErrors(['sekolah_id']);

    expect(TahunAkademik::where('name', '2026/2027')->whereNull('sekolah_id')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->postJson(route('admin.master-data.jenis-tagihan.store'), [
            'name' => 'Biaya Lab',
            'default_amount' => 100000,
            'is_active' => '1',
        ])
        ->assertCreated()
        ->assertJsonMissingValidationErrors(['sekolah_id']);

    expect(JenisTagihan::where('name', 'Biaya Lab')->whereNull('sekolah_id')->exists())->toBeTrue();
});

test('tagihan page loads universal tahun and jenis options', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $fixture->sekolah->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.keuangan.tagihan.index'));

    $response->assertOk();
    $response->assertSee('bersifat universal', false);
    $response->assertSee('name="tahun_akademik_id"', false);
    $response->assertSee('name="jenis_tagihan_id"', false);
    $response->assertSee((string) $fixture->tahun->id, false);
    $response->assertSee((string) $fixture->spp->id, false);
});

test('super admin can store tagihan without sekolah_id using student school master data', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $super = FinanceFixtures::adminUser([
        'username' => 'super.tagihan',
        'email' => 'super.tagihan@local.test',
    ]);
    $super->syncRoles(['super_admin']);

    $this->actingAs($super)
        ->postJson(route('admin.keuangan.tagihan.store'), [
            'siswa_id' => $fixture->siswa->id,
            'tahun_akademik_id' => $fixture->tahun->id,
            'jenis_tagihan_id' => $fixture->spp->id,
            'amount' => 500000,
            'periode' => '2026-01',
        ])
        ->assertCreated()
        ->assertJsonMissingValidationErrors(['sekolah_id']);
});

test('exists validation for jenis and tahun does not require sekolah_id field', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $admin = FinanceFixtures::adminUser(['sekolah_id' => $fixture->sekolah->id]);

    $this->actingAs($admin)
        ->postJson(route('admin.keuangan.tagihan.store'), [
            'siswa_id' => $fixture->siswa->id,
            'tahun_akademik_id' => $fixture->tahun->id,
            'jenis_tagihan_id' => $fixture->spp->id,
            'amount' => 500000,
            'periode' => '2026-01',
            // explicitly no sekolah_id
        ])
        ->assertCreated()
        ->assertJsonMissingValidationErrors(['sekolah_id', 'tahun_akademik_id', 'jenis_tagihan_id']);
});
