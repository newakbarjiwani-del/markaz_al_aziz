<?php

use App\Models\JenisTagihan;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\RiwayatAkademik;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Support\MasterDataUsage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->admin = User::create([
        'username' => 'admin.master',
        'name' => 'Admin Master',
        'email' => 'admin-master@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->admin->assignRole('admin');

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'Madrasah Aliyah (MA)',
        'address' => 'Tigamaya',
        'is_active' => true,
    ]);

    $this->admin->update(['sekolah_id' => $this->sekolah->id]);
});

test('admin can create and list sekolah master data', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.master-data.sekolah.store'), [
            'code' => 'mts',
            'name' => 'Madrasah Tsanawiyah',
            'address' => 'Pekanbaru',
            'phone' => '0254123400',
            'is_active' => '1',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->actingAs($this->admin)
        ->get(route('admin.master-data.sekolah.data'))
        ->assertOk()
        ->assertJsonPath('data.0.0', 'ma');
});

test('sekolah in use cannot be updated or deleted', function () {
    Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas' => 'X',
        'kelompok' => 'IPA 1',
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.master-data.sekolah.update', $this->sekolah), [
            'code' => 'ma',
            'name' => 'Nama Baru',
            'is_active' => '1',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    $this->actingAs($this->admin)
        ->delete(route('admin.master-data.sekolah.destroy', $this->sekolah))
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('admin can manage kelas master data', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.master-data.kelas.store'), [
            'sekolah_id' => $this->sekolah->id,
            'kelas' => 'XII',
            'kelompok' => 'IPA 1',
            'unit' => 'MA',
            'is_active' => '1',
        ])
        ->assertCreated();

    expect(Kelas::where('name', 'XII IPA 1')->exists())->toBeTrue();
});

test('kelas with siswa cannot be updated or deleted', function () {
    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas' => 'X',
        'kelompok' => 'IPA 1',
        'name' => '10IPA 1',
        'is_active' => true,
    ]);

    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000001',
        'name' => 'Siswa Test',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.master-data.kelas.update', $kelas), [
            'sekolah_id' => $this->sekolah->id,
            'kelas' => 'X',
            'kelompok' => 'IPA 2',
            'is_active' => '1',
        ])
        ->assertStatus(422);

    $this->actingAs($this->admin)
        ->delete(route('admin.master-data.kelas.destroy', $kelas))
        ->assertStatus(422);
});

test('admin can manage tahun akademik and enforce single active year', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.master-data.tahun-akademik.store'), [
            'name' => '2024/2025',
            'is_active' => '1',
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->post(route('admin.master-data.tahun-akademik.store'), [
            'name' => '2025/2026',
            'is_active' => '1',
        ])
        ->assertCreated();

    expect(TahunAkademik::where('is_active', true)->count())->toBe(1)
        ->and(TahunAkademik::where('name', '2025/2026')->value('is_active'))->toBeTruthy();
});

test('tahun akademik name must use YYYY/YYYY format', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.master-data.tahun-akademik.store'), [
            'name' => '2025-2026',
            'is_active' => '1',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name'])
        ->assertJsonMissingValidationErrors(['sekolah_id']);

    $this->actingAs($this->admin)
        ->postJson(route('admin.master-data.tahun-akademik.store'), [
            'name' => '2025/2027',
            'is_active' => '1',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('tahun akademik in use cannot be updated or deleted', function () {
    $tahun = TahunAkademik::create([
        'name' => '2025/2026',
        'is_active' => true,
    ]);

    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000002',
        'name' => 'Siswa Dua',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    RiwayatAkademik::create([
        'siswa_id' => $siswa->id,
        'tahun_akademik_id' => $tahun->id,
        'class_name' => 'X IPA 1',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.master-data.tahun-akademik.update', $tahun), [
            'name' => '2026/2027',
            'is_active' => '1',
        ])
        ->assertStatus(422);

    $this->actingAs($this->admin)
        ->delete(route('admin.master-data.tahun-akademik.destroy', $tahun))
        ->assertStatus(422);
});

test('unused master data can be deleted', function () {
    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Kelas Kosong',
        'is_active' => true,
    ]);

    $tahun = TahunAkademik::create([
        'name' => '2023/2024',
        'is_active' => false,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.master-data.kelas.destroy', $kelas))
        ->assertOk();

    $this->actingAs($this->admin)
        ->delete(route('admin.master-data.tahun-akademik.destroy', $tahun))
        ->assertOk();

    $unusedSekolah = Sekolah::create([
        'code' => 'demo',
        'name' => 'Sekolah Demo',
        'is_active' => true,
    ]);

    $superAdmin = User::create([
        'username' => 'super.master',
        'name' => 'Super Master',
        'email' => 'super-master@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)
        ->delete(route('admin.master-data.sekolah.destroy', $unusedSekolah))
        ->assertOk();
});

test('admin can manage jenis tagihan master data', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.master-data.jenis-tagihan.store'), [
            'name' => 'SPP',
            'code' => 'spp',
            'default_amount' => '500000',
            'is_spp' => '1',
            'sort_order' => '1',
            'is_active' => '1',
        ])
        ->assertCreated()
        ->assertJsonMissingValidationErrors(['sekolah_id']);

    $this->actingAs($this->admin)
        ->post(route('admin.master-data.jenis-tagihan.store'), [
            'name' => 'Uang Gedung',
            'default_amount' => '2000000',
            'is_spp' => '0',
            'is_active' => '1',
        ])
        ->assertCreated();

    expect(JenisTagihan::where('is_spp', true)->count())->toBe(1);

    $this->actingAs($this->admin)
        ->get(route('admin.master-data.jenis-tagihan.data'))
        ->assertOk()
        ->assertJsonPath('data.0.0', 'SPP');
});

test('admin can seed monthly SPP jenis tagihan', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.master-data.jenis-tagihan.seed-spp-bulanan'))
        ->assertOk()
        ->assertJsonPath('success', true);

    $expected = [
        'SPP JANUARI', 'SPP FEBRUARI', 'SPP MARET', 'SPP APRIL', 'SPP MEI', 'SPP JUNI',
        'SPP JULI', 'SPP AGUSTUS', 'SPP SEPTEMBER', 'SPP OKTOBER', 'SPP NOVEMBER', 'SPP DESEMBER',
    ];

    foreach ($expected as $name) {
        expect(
            JenisTagihan::where('name', $name)
                ->exists()
        )->toBeTrue();
    }
});

test('jenis tagihan in use cannot be deleted', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $this->admin->update(['sekolah_id' => $fixture->sekolah->id]);

    FinanceFixtures::tagihan($fixture->siswa, $fixture->spp, $fixture->tahun);

    $this->actingAs($this->admin->fresh())
        ->delete(route('admin.master-data.jenis-tagihan.destroy', $fixture->spp))
        ->assertStatus(422);
});

test('jenis tagihan in use allows updating default amount and spp flag', function () {
    $fixture = FinanceFixtures::schoolWithStudent();
    $this->admin->update(['sekolah_id' => $fixture->sekolah->id]);

    $other = FinanceFixtures::jenisTagihan($fixture->sekolah, [
        'name' => 'Uang Gedung',
        'default_amount' => 2000000,
        'is_spp' => false,
    ]);

    FinanceFixtures::tagihan($fixture->siswa, $fixture->spp, $fixture->tahun);

    expect(MasterDataUsage::jenisTagihanBlockedReason($fixture->spp))->toBe('Masih digunakan pada data tagihan.');

    $this->actingAs($this->admin->fresh())
        ->putJson(route('admin.master-data.jenis-tagihan.update', $fixture->spp), [
            'default_amount' => '750000',
            'is_spp' => '0',
        ])
        ->assertOk();

    $fixture->spp->refresh();
    $other->refresh();

    expect((float) $fixture->spp->default_amount)->toBe(750000.0)
        ->and($fixture->spp->is_spp)->toBeFalse()
        ->and($fixture->spp->name)->toBe('SPP');

    $this->actingAs($this->admin->fresh())
        ->putJson(route('admin.master-data.jenis-tagihan.update', $other), [
            'name' => $other->name,
            'default_amount' => '2100000',
            'is_spp' => '1',
            'is_active' => '1',
        ])
        ->assertOk()
        ->assertJsonMissingValidationErrors(['sekolah_id']);

    $other->refresh();
    $fixture->spp->refresh();

    expect($other->is_spp)->toBeTrue()
        ->and((float) $other->default_amount)->toBe(2100000.0)
        ->and($fixture->spp->is_spp)->toBeFalse();
});

test('admin can create kamar and status santri master data', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.master-data.kamar.store'), [
            'kode' => 'A-01',
            'nama' => 'A-01',
            'blok' => 'Asrama A',
            'kapasitas' => 4,
            'sort_order' => 1,
            'is_active' => '1',
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->post(route('admin.master-data.status-santri.store'), [
            'nama' => 'Santri',
            'sort_order' => 1,
            'is_active' => '1',
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->get(route('admin.master-data.kamar.data'))
        ->assertOk()
        ->assertJsonPath('data.0.1', 'A-01');

    $this->actingAs($this->admin)
        ->get(route('admin.master-data.status-santri.data'))
        ->assertOk()
        ->assertJsonPath('data.0.0', 'Santri');
});

test('kamar in use cannot be deleted', function () {
    $kamar = Kamar::create([
        'nama' => 'B-01',
        'blok' => 'Asrama B',
        'is_active' => true,
    ]);

    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'XI IPA 1',
        'unit' => 'MA',
        'is_active' => true,
    ]);

    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'kamar_id' => $kamar->id,
        'nis' => '9000001',
        'name' => 'Santri Kamar',
        'gender' => 'L',
        'status' => 1,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.master-data.kamar.destroy', $kamar))
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});
