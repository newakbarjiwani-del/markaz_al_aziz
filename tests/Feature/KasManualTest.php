<?php

use App\Models\KasManual;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'A',
        'is_active' => true,
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.kas',
        'name' => 'Admin Kas',
        'email' => 'admin-kas@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('admin can store pemasukan kas manual entry', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kas-manual.store'), [
            'tanggal' => '2026-07-10',
            'kategori' => 'Donasi',
            'deskripsi' => 'Donasi orang tua',
            'arah' => 'pemasukan',
            'nominal' => 250000,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('kas_manual', [
        'kategori' => 'Donasi',
        'kredit' => 250000,
        'debet' => null,
        'created_by' => $this->admin->id,
    ]);
});

test('admin can store pengeluaran kas manual entry', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kas-manual.store'), [
            'tanggal' => '2026-07-10',
            'kategori' => 'Operasional',
            'arah' => 'pengeluaran',
            'nominal' => 150000,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('kas_manual', [
        'kategori' => 'Operasional',
        'kredit' => null,
        'debet' => 150000,
    ]);
});

test('kas manual store requires nominal and arah', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kas-manual.store'), [
            'tanggal' => '2026-07-10',
            'kategori' => 'Operasional',
            'arah' => 'pemasukan',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nominal']);
});

test('admin can update kas manual entry', function () {
    $kas = KasManual::create([
        'sekolah_id' => $this->sekolah->id,
        'created_by' => $this->admin->id,
        'tanggal' => '2026-07-01',
        'kategori' => 'Infaq',
        'kredit' => 100000,
        'debet' => null,
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.keuangan.kas-manual.update', $kas), [
            'tanggal' => '2026-07-02',
            'kategori' => 'Infaq Jumat',
            'arah' => 'pengeluaran',
            'nominal' => 75000,
            'deskripsi' => 'Beli ATK',
        ])
        ->assertOk();

    $kas->refresh();
    expect($kas->kategori)->toBe('Infaq Jumat')
        ->and($kas->kredit)->toBeNull()
        ->and($kas->debet)->toBe(75000)
        ->and($kas->deskripsi)->toBe('Beli ATK');
});

test('kas manual stats respect date filter', function () {
    KasManual::create([
        'sekolah_id' => $this->sekolah->id,
        'created_by' => $this->admin->id,
        'tanggal' => '2026-07-01',
        'kategori' => 'Donasi',
        'kredit' => 300000,
    ]);

    KasManual::create([
        'sekolah_id' => $this->sekolah->id,
        'created_by' => $this->admin->id,
        'tanggal' => '2026-08-01',
        'kategori' => 'Operasional',
        'debet' => 50000,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.kas-manual.stats', [
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]))
        ->assertOk();

    expect($response->json('data.total_kredit'))->toBe(300000)
        ->and($response->json('data.total_debet'))->toBe(0)
        ->and($response->json('data.saldo_bersih'))->toBe(300000);
});

test('kas manual kategori lookup returns ajax select format', function () {
    KasManual::create([
        'sekolah_id' => $this->sekolah->id,
        'created_by' => $this->admin->id,
        'tanggal' => '2026-07-01',
        'kategori' => 'Donasi Alumni',
        'kredit' => 10000,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.kas-manual.kategori', ['term' => 'donasi']))
        ->assertOk()
        ->assertJsonPath('results.0.id', 'Donasi Alumni')
        ->assertJsonPath('results.0.text', 'Donasi Alumni');
});
