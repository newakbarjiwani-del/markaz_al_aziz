<?php

use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'Madrasah Aliyah (MA)',
        'address' => 'Tigamaya',
    ]);

    $this->admin = User::create([
        'username' => 'admin.ortu',
        'name' => 'Admin Ortu',
        'email' => 'admin-ortu@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('orang tua store requires at least one parent or guardian name', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'sekolah_id' => $this->sekolah->id,
            'alamat' => 'Jl. Contoh',
            'status' => 'aktif',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nama_ayah']);
});

test('orang tua can be stored with wali only', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'sekolah_id' => $this->sekolah->id,
            'nama_wali' => 'Habib Abdullah',
            'telepon_wali' => '081234567890',
            'status' => 'aktif',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $orangTua = OrangTua::first();

    expect($orangTua?->nama_wali)->toBe('Habib Abdullah')
        ->and($orangTua?->displayName())->toBe('Habib Abdullah');
});

test('orang tua can be stored with father name only', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'sekolah_id' => $this->sekolah->id,
            'nama_ayah' => 'Bapak Ahmad',
            'telepon_ayah' => '081111111111',
        ])
        ->assertCreated();

    expect(OrangTua::count())->toBe(1);
});

test('orang tua store normalizes phone from user input without auto generation', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'sekolah_id' => $this->sekolah->id,
            'nama_wali' => 'Wali Test',
            'telepon_wali' => '081234567890',
            'status' => 'aktif',
        ])
        ->assertCreated();

    expect(OrangTua::first()?->telepon_wali)->toBe('6281234567890');
});

test('orang tua store leaves phone and email null when not provided', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'sekolah_id' => $this->sekolah->id,
            'nama_wali' => 'Wali Tanpa Kontak',
            'telepon_wali' => '',
            'email_wali' => '',
            'status' => 'aktif',
        ])
        ->assertCreated();

    $orangTua = OrangTua::first();

    expect($orangTua?->telepon_wali)->toBeNull()
        ->and($orangTua?->email_wali)->toBeNull()
        ->and($orangTua?->telepon_ayah)->toBeNull()
        ->and($orangTua?->email_ayah)->toBeNull();
});

test('orang tua update rejects clearing all contact names', function () {
    $orangTua = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_wali' => 'Wali Awal',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.orang-tua.update', $orangTua), [
            'sekolah_id' => $this->sekolah->id,
            'nama_wali' => '',
            'nama_ayah' => '',
            'nama_ibu' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nama_ayah']);
});

test('orang tua store rejects identical phones for ayah ibu or wali', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'sekolah_id' => $this->sekolah->id,
            'nama_ayah' => 'Ahmad',
            'telepon_ayah' => '081234567890',
            'nama_ibu' => 'Ahmad',
            'telepon_ibu' => '081234567890',
            'status' => 'aktif',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['telepon_ayah', 'telepon_ibu']);
});

test('orang tua can be stored when ayah and ibu share the same name but different phones', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'sekolah_id' => $this->sekolah->id,
            'nama_ayah' => 'Ahmad',
            'telepon_ayah' => '081111111111',
            'nama_ibu' => 'Ahmad',
            'telepon_ibu' => '082222222222',
            'status' => 'aktif',
        ])
        ->assertCreated();

    expect(OrangTua::count())->toBe(1);
});

test('orang tua store rejects phone already used on another parent record', function () {
    OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Adriansyah',
        'telepon_ayah' => '6281200003050',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'sekolah_id' => $this->sekolah->id,
            'nama_ayah' => 'Adriansyah',
            'telepon_ayah' => '081200003050',
            'status' => 'aktif',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['telepon_ayah']);
});

test('orang tua update allows keeping own phone but rejects phone from another parent', function () {
    $self = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Ayah Sendiri',
        'telepon_ayah' => '6281111111111',
        'status' => 'aktif',
    ]);

    OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ibu' => 'Ibu Lain',
        'telepon_ibu' => '6282222222222',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.orang-tua.update', $self), [
            'sekolah_id' => $this->sekolah->id,
            'nama_ayah' => 'Ayah Sendiri',
            'telepon_ayah' => '081111111111',
            'status' => 'aktif',
        ])
        ->assertOk();

    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.orang-tua.update', $self), [
            'sekolah_id' => $this->sekolah->id,
            'nama_ayah' => 'Ayah Sendiri',
            'telepon_ayah' => '082222222222',
            'status' => 'aktif',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['telepon_ayah']);
});

test('unscoped admin can store orang tua without sekolah', function () {
    $unscoped = User::create([
        'username' => 'admin.ortu.lintas',
        'name' => 'Admin Ortu Lintas',
        'email' => 'admin-ortu-lintas@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $unscoped->assignRole('admin');

    $this->actingAs($unscoped)
        ->postJson(route('admin.manajemen-siswa.orang-tua.store'), [
            'nama_ayah' => 'Ayah Lintas',
            'telepon_ayah' => '081333333333',
            'status' => 'aktif',
        ])
        ->assertCreated();

    expect(OrangTua::where('nama_ayah', 'Ayah Lintas')->value('sekolah_id'))->toBeNull();
});

test('orang tua data can be filtered by linked student name', function () {
    $kelas = \App\Models\Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    $siswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000999',
        'name' => 'Ahmad Anak Khusus',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $matched = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Ayah Match',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);
    $matched->siswa()->attach($siswa->id);

    OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Ayah Lain',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'nama_siswa' => 'Ahmad Anak',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.0', 'Ayah Match');
});

test('orang tua data can be filtered by linked student NIS', function () {
    $kelas = \App\Models\Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    $siswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000999',
        'name' => 'Budi Siswa NIS',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $matched = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Ayah NIS Match',
        'status' => 'aktif',
    ]);
    $matched->siswa()->attach($siswa->id);

    OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Ayah Lain',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'nis_siswa' => '1000999',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.0', 'Ayah NIS Match');
});

test('orang tua data can be filtered by father name', function () {
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak Ahmad', 'status' => 'aktif']);
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak Budi', 'status' => 'aktif']);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'nama_ayah' => 'Ahmad',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.0', 'Bapak Ahmad');
});

test('orang tua data can be filtered by mother name', function () {
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak X', 'nama_ibu' => 'Ibu Siti', 'status' => 'aktif']);
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak Y', 'nama_ibu' => 'Ibu Dewi', 'status' => 'aktif']);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'nama_ibu' => 'Siti',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.0', 'Bapak X');
});

test('orang tua data can be filtered by guardian name', function () {
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_wali' => 'Habib Abdullah', 'status' => 'aktif']);
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_wali' => 'Habib Umar', 'status' => 'aktif']);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'nama_wali' => 'Abdullah',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.4', 'Habib Abdullah');
});

test('orang tua data filter combines father and student name', function () {
    $kelas = \App\Models\Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    $siswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000888',
        'name' => 'Rina Kombinasi',
        'gender' => 'P',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $matched = OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak Kombinasi', 'status' => 'aktif']);
    $matched->siswa()->attach($siswa->id);

    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak Kombinasi', 'status' => 'aktif']);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'nama_ayah' => 'Kombinasi',
            'nama_siswa' => 'Rina',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.0', 'Bapak Kombinasi');
});

test('orang tua data sort by mother name column uses nama_ibu', function () {
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak A', 'nama_ibu' => 'Ibu Budi', 'status' => 'aktif']);
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak B', 'nama_ibu' => 'Ibu Ahmad', 'status' => 'aktif']);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'order' => [['column' => 2, 'dir' => 'asc']],
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.2', 'Ibu Ahmad');
});

test('orang tua data sort by guardian name column uses nama_wali', function () {
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_wali' => 'Habib Umar', 'status' => 'aktif']);
    OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_wali' => 'Habib Ahmad', 'status' => 'aktif']);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'order' => [['column' => 4, 'dir' => 'asc']],
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.4', 'Habib Ahmad');
});

test('orang tua data sort by jumlah anak column uses siswa_count', function () {
    $a = OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak Dua', 'status' => 'aktif']);
    $b = OrangTua::create(['sekolah_id' => $this->sekolah->id, 'nama_ayah' => 'Bapak Satu', 'status' => 'aktif']);

    $kelas = \App\Models\Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    foreach (['Siswa 1', 'Siswa 2'] as $name) {
        $s = \App\Models\Siswa::create([
            'sekolah_id' => $this->sekolah->id,
            'kelas_id' => $kelas->id,
            'name' => $name,
            'nis' => (string) rand(100000, 999999),
            'gender' => 'L',
            'status' => \App\Models\Siswa::STATUS_ACTIVE,
        ]);
        $a->siswa()->attach($s);
    }

    foreach (['Siswa 3'] as $name) {
        $s = \App\Models\Siswa::create([
            'sekolah_id' => $this->sekolah->id,
            'kelas_id' => $kelas->id,
            'name' => $name,
            'nis' => (string) rand(100000, 999999),
            'gender' => 'L',
            'status' => \App\Models\Siswa::STATUS_ACTIVE,
        ]);
        $b->siswa()->attach($s);
    }

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'order' => [['column' => 6, 'dir' => 'asc']],
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.6', 1)
        ->assertJsonPath('data.1.6', 2);
});
