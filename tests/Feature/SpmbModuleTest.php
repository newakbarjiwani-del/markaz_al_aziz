<?php

use App\Models\Dompet;
use App\Models\KartuSiswa;
use App\Models\ProfilSiswa;
use App\Models\SaldoKeuangan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\SpmbPendaftar;
use App\Models\SpmbPeriode;
use App\Models\User;
use App\Services\SpmbAcceptanceService;
use App\Support\SpmbRegistrationNumber;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sekolah = Sekolah::create([
        'code' => 'spmb',
        'name' => 'Sekolah SPMB Test',
        'address' => 'Jl. Test',
    ]);

    $this->periode = SpmbPeriode::create([
        'name' => 'SPMB Test 2026',
        'sekolah_id' => $this->sekolah->id,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addMonth(),
        'is_active' => true,
    ]);

    $this->service = app(SpmbAcceptanceService::class);
});

test('generateNomorPendaftaran follows SPMB-Ymd-NNNN pattern and is unique', function () {
    $one = $this->service->generateNomorPendaftaran();
    $two = SpmbRegistrationNumber::generate();

    expect($one)->toMatch('/^SPMB-\d{8}-\d{4}$/')
        ->and($two)->toMatch('/^SPMB-\d{8}-\d{4}$/')
        ->and($one)->not->toBe($two);
});

test('accept creates active siswa with wallets and links pendaftar', function () {
    $pendaftar = SpmbPendaftar::create([
        'spmb_periode_id' => $this->periode->id,
        'sekolah_id' => $this->sekolah->id,
        'nomor_pendaftaran' => $this->service->generateNomorPendaftaran(),
        'name' => 'Ahmad Calon',
        'gender' => 'L',
        'status' => SpmbPendaftar::STATUS_VERIFIED,
    ]);

    $siswa = $this->service->accept($pendaftar, '20260001');

    expect($siswa)->toBeInstanceOf(Siswa::class)
        ->and($siswa->status)->toBe(Siswa::STATUS_ACTIVE)
        ->and($siswa->nis)->toBe('20260001')
        ->and($siswa->nomor_pendaftaran)->toBe($pendaftar->nomor_pendaftaran)
        ->and($siswa->sekolah_id)->toBe($this->sekolah->id);

    expect(Dompet::query()->where('siswa_id', $siswa->id)->exists())->toBeTrue()
        ->and(SaldoKeuangan::query()->where('siswa_id', $siswa->id)->exists())->toBeTrue()
        ->and(KartuSiswa::query()->where('siswa_id', $siswa->id)->exists())->toBeTrue()
        ->and(ProfilSiswa::query()->where('siswa_id', $siswa->id)->exists())->toBeTrue();

    $pendaftar->refresh();
    expect($pendaftar->status)->toBe(SpmbPendaftar::STATUS_ACCEPTED)
        ->and($pendaftar->siswa_id)->toBe($siswa->id);
});

test('accept rejects non-verified pendaftar', function () {
    $pendaftar = SpmbPendaftar::create([
        'spmb_periode_id' => $this->periode->id,
        'sekolah_id' => $this->sekolah->id,
        'nomor_pendaftaran' => $this->service->generateNomorPendaftaran(),
        'name' => 'Belum Verified',
        'status' => SpmbPendaftar::STATUS_SUBMITTED,
    ]);

    $this->service->accept($pendaftar, '20260002');
})->throws(ValidationException::class);

test('reject marks pendaftar rejected with notes', function () {
    $pendaftar = SpmbPendaftar::create([
        'spmb_periode_id' => $this->periode->id,
        'sekolah_id' => $this->sekolah->id,
        'nomor_pendaftaran' => $this->service->generateNomorPendaftaran(),
        'name' => 'Ditolak',
        'status' => SpmbPendaftar::STATUS_SUBMITTED,
    ]);

    $this->service->reject($pendaftar, 'Dokumen tidak lengkap');

    $pendaftar->refresh();
    expect($pendaftar->status)->toBe(SpmbPendaftar::STATUS_REJECTED)
        ->and($pendaftar->notes)->toBe('Dokumen tidak lengkap');
});

test('reject fails when already accepted', function () {
    $pendaftar = SpmbPendaftar::create([
        'spmb_periode_id' => $this->periode->id,
        'sekolah_id' => $this->sekolah->id,
        'nomor_pendaftaran' => $this->service->generateNomorPendaftaran(),
        'name' => 'Sudah Diterima',
        'status' => SpmbPendaftar::STATUS_ACCEPTED,
    ]);

    $this->service->reject($pendaftar, 'late');
})->throws(ValidationException::class);

test('public spmb landing and registration work when periode is open', function () {
    $this->get(route('spmb.home'))
        ->assertOk()
        ->assertSee('SPMB');

    $this->get(route('spmb.daftar'))
        ->assertOk();

    $this->post(route('spmb.daftar.store'), [
        'name' => 'Calon Baru',
        'gender' => 'L',
        'birth_place' => 'Pekanbaru',
        'birth_date' => '2012-01-01',
        'address' => 'Jl. Calon',
        'phone' => '081234567890',
        'parent_name' => 'Orang Tua',
        'parent_phone' => '081298765432',
    ])->assertRedirect(route('spmb.daftar'));

    $this->assertDatabaseHas('spmb_pendaftar', [
        'name' => 'Calon Baru',
        'status' => SpmbPendaftar::STATUS_SUBMITTED,
        'sekolah_id' => $this->sekolah->id,
    ]);
});

test('admin can open spmb dashboard with permission', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create([
        'username' => 'spmb.admin',
        'status' => 'aktif',
    ]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.spmb.dashboard'))
        ->assertOk();
});
