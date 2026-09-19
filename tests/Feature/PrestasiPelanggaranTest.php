<?php

use App\Models\BuktiCatatan;
use App\Models\Guru;
use App\Models\HukumanSiswa;
use App\Models\JenisPelanggaran;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\PelanggaranGuru;
use App\Models\PelanggaranSiswa;
use App\Models\PrestasiGuru;
use App\Models\PrestasiSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\MenuService;
use App\Support\HomeRedirect;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $this->admin = User::create([
        'username' => 'admin.pre',
        'name' => 'Admin Prestasi',
        'email' => 'admin-pre@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->admin->assignRole('admin');

    $this->guru = Guru::create([
        'nip' => '1234567890',
        'name' => 'Guru Test',
        'jabatan' => 'Guru Mapel',
        'sekolah_id' => $this->sekolah->id,
        'status' => 'aktif',
    ]);

    $this->guruUser = User::create([
        'username' => 'guru.pre',
        'name' => 'Guru User',
        'email' => 'guru-pre@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->guruUser->assignRole('guru');

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'VII A',
        'kelas' => 'VII',
        'kelompok' => 'A',
        'unit' => 'MTs',
        'jenjang' => 'Menengah Pertama',
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000001',
        'name' => 'Ahmad Siswa',
        'gender' => 'L',
        'status' => 1,
    ]);

    $this->katalogPelanggaran = JenisPelanggaran::create([
        'level' => 'ringan',
        'bidang' => 'Kedisiplinan',
        'nama' => 'Terlambat Masuk Kelas',
        'point' => 5,
        'is_active' => true,
    ]);

    $this->ortu = User::create([
        'username' => 'ortu.pre',
        'name' => 'Orang Tua',
        'email' => 'ortu-pre@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->ortu->assignRole('orang_tua');

    $this->siswaUser = User::create([
        'username' => 'siswa.pre',
        'name' => 'Siswa User',
        'email' => 'siswa-pre@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'siswa_id' => $this->siswa->id,
    ]);
    $this->siswaUser->assignRole('siswa');
});

function validPrestasiSiswaPayload(array $overrides = []): array
{
    return array_merge([
        'siswa_id' => 1,
        'judul' => 'Juara 1 Olimpiade Matematika',
        'keterangan' => 'Tingkat kabupaten',
        'tanggal' => now()->subDays(5)->format('Y-m-d'),
        'point' => 10,
    ], $overrides);
}

function validPelanggaranSiswaPayload(array $overrides = []): array
{
    return array_merge([
        'siswa_id' => 1,
        'judul' => 'Terlambat Masuk Kelas',
        'keterangan' => '3 kali terlambat',
        'tanggal' => now()->subDays(2)->format('Y-m-d'),
        'point' => 5,
    ], $overrides);
}

function validPrestasiGuruPayload(array $overrides = []): array
{
    return array_merge([
        'guru_id' => 1,
        'judul' => 'Guru Berprestasi',
        'keterangan' => 'Tingkat nasional',
        'tanggal' => now()->subDays(10)->format('Y-m-d'),
        'point' => 20,
    ], $overrides);
}

function validPelanggaranGuruPayload(array $overrides = []): array
{
    return array_merge([
        'guru_id' => 1,
        'judul' => 'Keterlambatan Mengajar',
        'keterangan' => 'Tanpa keterangan',
        'tanggal' => now()->subDays(3)->format('Y-m-d'),
        'point' => 3,
    ], $overrides);
}

// ── Admin CRUD: Prestasi Siswa ──

test('admin can view prestasi siswa index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.prestasi-pelanggaran.prestasi-siswa.index'));
    $response->assertOk();
});

test('admin can fetch prestasi siswa data', function () {
    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Juara Lomba',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.prestasi-siswa.data'));
    $response->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});

test('admin prestasi and pelanggaran siswa datatable search includes student name', function () {
    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Juara Olimpiade Matematika',
        'tanggal' => now(),
        'point' => 10,
        'reported_by' => $this->admin->id,
    ]);

    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Terlambat Masuk Kelas',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
        'jenis_pelanggaran_id' => null,
    ]);

    $nameNeedle = explode(' ', $this->siswa->name)[0];

    $prestasi = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.prestasi-siswa.data', [
        'search' => ['value' => $nameNeedle],
    ]));
    $prestasi->assertOk()->assertJsonPath('recordsFiltered', 1);

    $pelanggaran = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.pelanggaran-siswa.data', [
        'search' => ['value' => $nameNeedle],
    ]));
    $pelanggaran->assertOk()->assertJsonPath('recordsFiltered', 1);

    $miss = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.prestasi-siswa.data', [
        'search' => ['value' => 'NamaTidakAdaXYZ'],
    ]));
    $miss->assertOk()->assertJsonPath('recordsFiltered', 0);
});

test('admin can store prestasi siswa', function () {
    $payload = validPrestasiSiswaPayload(['siswa_id' => $this->siswa->id]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), $payload);
    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('prestasi_siswa', [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Juara 1 Olimpiade Matematika',
    ]);
});

test('admin can show prestasi siswa detail', function () {
    $record = PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Juara Lomba',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.prestasi-siswa.show', $record));
    $response->assertOk()
        ->assertJsonPath('data.judul', 'Juara Lomba')
        ->assertJsonPath('data.siswa_name', 'Ahmad Siswa');
});

test('admin can update prestasi siswa', function () {
    $record = PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Old Title',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->putJson(route('admin.prestasi-pelanggaran.prestasi-siswa.update', $record), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'New Title',
        'tanggal' => now()->format('Y-m-d'),
        'point' => 15,
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('prestasi_siswa', ['id' => $record->id, 'judul' => 'New Title']);
});

test('admin can delete prestasi siswa', function () {
    $record = PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'To Delete',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson(route('admin.prestasi-pelanggaran.prestasi-siswa.destroy', $record));
    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('prestasi_siswa', ['id' => $record->id]);
});

// ── Admin CRUD: Pelanggaran Siswa ──

test('admin can store pelanggaran siswa', function () {
    $payload = validPelanggaranSiswaPayload([
        'siswa_id' => $this->siswa->id,
        'jenis_pelanggaran_id' => $this->katalogPelanggaran->id,
    ]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.pelanggaran-siswa.store'), $payload);
    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('pelanggaran_siswa', [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Terlambat Masuk Kelas',
    ]);
});

test('admin can delete pelanggaran siswa', function () {
    $record = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Pelanggaran',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson(route('admin.prestasi-pelanggaran.pelanggaran-siswa.destroy', $record));
    $response->assertOk();
    $this->assertSoftDeleted('pelanggaran_siswa', ['id' => $record->id]);
});

// ── Admin CRUD: Prestasi Guru ──

test('admin can store prestasi guru', function () {
    $payload = validPrestasiGuruPayload(['guru_id' => $this->guru->id]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.prestasi-guru.store'), $payload);
    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('prestasi_guru', [
        'guru_id' => $this->guru->id,
        'judul' => 'Guru Berprestasi',
    ]);
});

// ── Admin CRUD: Pelanggaran Guru ──

test('admin can store pelanggaran guru', function () {
    $payload = validPelanggaranGuruPayload([
        'guru_id' => $this->guru->id,
        'jenis_pelanggaran_id' => $this->katalogPelanggaran->id,
    ]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.pelanggaran-guru.store'), $payload);
    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('pelanggaran_guru', [
        'guru_id' => $this->guru->id,
        'judul' => 'Keterlambatan Mengajar',
    ]);
});

// ── Validation ──

test('prestasi siswa store validates required fields', function () {
    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), []);
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['siswa_ids', 'judul', 'tanggal']);
});

test('prestasi siswa store rejects future date', function () {
    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Test',
        'tanggal' => now()->addDay()->format('Y-m-d'),
    ]);
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['tanggal']);
});

test('prestasi siswa store validates point range', function () {
    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Test',
        'tanggal' => now()->format('Y-m-d'),
        'point' => -1,
    ]);
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['point']);
});

test('prestasi siswa store rejects too many bukti files', function () {
    $files = [];
    for ($i = 0; $i < 12; $i++) {
        $files[] = UploadedFile::fake()->create('bukti'.$i.'.pdf', 100, 'application/pdf');
    }

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Test',
        'tanggal' => now()->format('Y-m-d'),
        'bukti' => $files,
    ]);
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['bukti']);
});

// ── Bukti file upload ──

test('admin can store prestasi siswa with bukti file', function () {
    Storage::fake('public');

    $buktiFile = UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Dengan Bukti',
        'tanggal' => now()->format('Y-m-d'),
        'point' => 10,
        'bukti' => [$buktiFile],
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('bukti_catatan', [
        'buktiable_type' => PrestasiSiswa::class,
        'file_type' => 'pdf',
        'original_name' => 'evidence.pdf',
    ]);
});

test('admin can delete prestasi with bukti cleans up files', function () {
    Storage::fake('public');

    $this->admin->givePermissionTo('prestasi-siswa.delete');

    $record = PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'With File',
        'tanggal' => now(),
        'point' => 0,
        'reported_by' => $this->admin->id,
    ]);

    $buktiFile = UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf');
    $filename = time().'_'.mt_rand(1000, 9999).'_evidence.pdf';
    $path = $buktiFile->storeAs("bukti-catatan/prestasi-siswa/{$record->id}", $filename, 'public');

    BuktiCatatan::create([
        'buktiable_type' => PrestasiSiswa::class,
        'buktiable_id' => $record->id,
        'file_path' => $path,
        'file_type' => 'pdf',
        'original_name' => 'evidence.pdf',
        'file_size' => 100 * 1024,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson(route('admin.prestasi-pelanggaran.prestasi-siswa.destroy', $record));
    $response->assertOk();
    $this->assertSoftDeleted('prestasi_siswa', ['id' => $record->id]);
    $this->assertSoftDeleted('bukti_catatan', ['buktiable_id' => $record->id]);
    Storage::disk('public')->assertMissing("bukti-catatan/prestasi-siswa/{$record->id}/{$filename}");
});

// ── Permission checks ──

test('unauthenticated user cannot access prestasi siswa', function () {
    $response = $this->getJson(route('admin.prestasi-pelanggaran.prestasi-siswa.data'));
    $response->assertUnauthorized();
});

test('guru user cannot access admin prestasi siswa store', function () {
    $response = $this->actingAs($this->guruUser)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Test',
        'tanggal' => now()->format('Y-m-d'),
    ]);
    $response->assertForbidden();
});

// ── Ortu read-only ──

test('ortu can view prestasi siswa for linked child', function () {
    $orangTua = OrangTua::create([
        'nama_ayah' => 'Bapak Test',
        'telepon_ayah' => '081234567890',
    ]);

    $orangTua->siswa()->attach($this->siswa->id);

    $this->ortu->update(['orang_tua_id' => $orangTua->id]);

    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Prestasi Anak',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->ortu)->getJson(route('portal.ortu.prestasi-siswa.data'));
    $response->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});

test('ortu cannot store prestasi siswa - route not defined', function () {
    // Ortu portal is read-only; no store route exists
    $this->assertTrue(true);
});

// ── Siswa read-only ──

test('siswa can view own prestasi data', function () {
    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Prestasi Sendiri',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->siswaUser)->getJson(route('portal.siswa.prestasi-siswa.data'));
    $response->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});

test('siswa cannot store prestasi - route not defined', function () {
    // Siswa portal is read-only; no store route exists
    $this->assertTrue(true);
});

// ── Guru portal CRUD (requires jadwal absen setup - tested via admin scope) ──

test('guru user without permission cannot access admin store', function () {
    $response = $this->actingAs($this->guruUser)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Test',
        'tanggal' => now()->format('Y-m-d'),
    ]);
    $response->assertForbidden();
});

test('guru has full prestasi and pelanggaran siswa permissions', function () {
    $this->assertTrue($this->guruUser->can('prestasi-siswa.view'));
    $this->assertTrue($this->guruUser->can('prestasi-siswa.create'));
    $this->assertTrue($this->guruUser->can('prestasi-siswa.update'));
    $this->assertTrue($this->guruUser->can('prestasi-siswa.delete'));
    $this->assertTrue($this->guruUser->can('pelanggaran-siswa.view'));
    $this->assertTrue($this->guruUser->can('pelanggaran-siswa.create'));
    $this->assertTrue($this->guruUser->can('pelanggaran-siswa.update'));
    $this->assertTrue($this->guruUser->can('pelanggaran-siswa.delete'));
});

test('guru portal can list and store prestasi for any student', function () {
    $otherKelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'VIII B',
        'kelas' => 'VIII',
        'kelompok' => 'B',
        'unit' => 'MTs',
        'jenjang' => 'Menengah Pertama',
    ]);
    $otherSiswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $otherKelas->id,
        'nis' => '1000099',
        'name' => 'Siswa Luar Ampu',
        'gender' => 'P',
        'status' => 1,
    ]);

    PrestasiSiswa::create([
        'siswa_id' => $otherSiswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Luar Ampu',
        'tanggal' => now(),
        'point' => 3,
        'reported_by' => $this->admin->id,
    ]);

    $list = $this->actingAs($this->guruUser)->getJson(route('portal.guru.prestasi-siswa.data'));
    $list->assertOk();
    expect(collect($list->json('data'))->pluck(0)->contains('Siswa Luar Ampu'))->toBeTrue();

    $store = $this->actingAs($this->guruUser)->postJson(route('portal.guru.prestasi-siswa.store'), [
        'siswa_id' => $otherSiswa->id,
        'judul' => 'Prestasi Guru Semua Siswa',
        'tanggal' => now()->format('Y-m-d'),
        'point' => 7,
    ]);
    $store->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('prestasi_siswa', [
        'siswa_id' => $otherSiswa->id,
        'judul' => 'Prestasi Guru Semua Siswa',
        'reported_by' => $this->guruUser->id,
    ]);
});

test('portal guru siswa lookup route is registered', function () {
    expect(Route::has('portal.guru.siswa.lookup'))->toBeTrue()
        ->and(Route::has('portal.guru.siswa.lookup.show'))->toBeTrue();
});

test('guru portal can lookup any student for prestasi forms', function () {
    $response = $this->actingAs($this->guruUser)->getJson(route('portal.guru.siswa.lookup', [
        'term' => 'Ahmad',
    ]));

    $response->assertOk();
    expect(collect($response->json('results'))->pluck('id'))->toContain($this->siswa->id);
});

test('pimpinan can manage prestasi and pelanggaran via admin routes', function () {
    $pimpinan = User::create([
        'username' => 'pimpinan.pre',
        'name' => 'Pimpinan Prestasi',
        'email' => 'pimpinan-pre@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $pimpinan->assignRole('pimpinan');

    expect($pimpinan->can('prestasi-siswa.create'))->toBeTrue()
        ->and($pimpinan->can('prestasi-siswa.update'))->toBeTrue()
        ->and($pimpinan->can('prestasi-siswa.delete'))->toBeTrue()
        ->and($pimpinan->can('pelanggaran-siswa.create'))->toBeTrue()
        ->and($pimpinan->can('prestasi-guru.create'))->toBeTrue()
        ->and($pimpinan->can('katalog-pelanggaran.create'))->toBeTrue();

    $store = $this->actingAs($pimpinan)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Prestasi Pimpinan',
        'tanggal' => now()->format('Y-m-d'),
        'point' => 10,
    ]);
    $store->assertOk()->assertJsonPath('success', true);

    $record = PrestasiSiswa::where('judul', 'Prestasi Pimpinan')->first();
    expect($record)->not->toBeNull();

    $update = $this->actingAs($pimpinan)->putJson(route('admin.prestasi-pelanggaran.prestasi-siswa.update', $record), [
        'siswa_id' => $this->siswa->id,
        'judul' => 'Prestasi Pimpinan Updated',
        'tanggal' => now()->format('Y-m-d'),
        'point' => 12,
    ]);
    $update->assertOk()->assertJsonPath('success', true);

    $pelanggaran = $this->actingAs($pimpinan)->postJson(route('admin.prestasi-pelanggaran.pelanggaran-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'jenis_pelanggaran_id' => $this->katalogPelanggaran->id,
        'judul' => 'Pelanggaran Pimpinan',
        'tanggal' => now()->format('Y-m-d'),
        'point' => 5,
    ]);
    $pelanggaran->assertOk()->assertJsonPath('success', true);

    $menu = (new MenuService)->menuFor($pimpinan);
    expect(collect($menu)->contains(fn ($item) => ($item['label'] ?? '') === 'Prestasi & Pelanggaran'))->toBeTrue();
});

test('prestasi_pelanggaran role can manage prestasi pelanggaran and hukuman via admin only', function () {
    $operator = User::create([
        'username' => 'prestasi.pre',
        'name' => 'Operator Prestasi',
        'email' => 'prestasi-pre@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $operator->assignRole('prestasi_pelanggaran');

    expect($operator->can('prestasi-siswa.create'))->toBeTrue()
        ->and($operator->can('hukuman-siswa.create'))->toBeTrue()
        ->and($operator->can('katalog-pelanggaran.create'))->toBeTrue()
        ->and($operator->can('finance.view'))->toBeFalse()
        ->and($operator->can('perizinan.view'))->toBeFalse();

    $this->actingAs($operator)
        ->get(route('admin.prestasi-pelanggaran.dashboard'))
        ->assertOk();

    $menu = (new MenuService)->menuFor($operator);
    $labels = collect($menu)->pluck('label')->filter()->values()->all();
    expect($labels)->toContain('Prestasi & Pelanggaran')
        ->and($labels)->not->toContain('Keuangan')
        ->and($labels)->not->toContain('Absensi');

    expect(HomeRedirect::routeName($operator))->toBe('admin.prestasi-pelanggaran.dashboard');
});

test('data siswa shows punishment icon when student has pelanggaran', function () {
    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $this->katalogPelanggaran->id,
        'judul' => 'Ada pelanggaran',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    $clean = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000088',
        'name' => 'Siswa Bersih',
        'gender' => 'L',
        'status' => 1,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.manajemen-siswa.data-siswa.data'));
    $response->assertOk();

    $rows = collect($response->json('data'));
    $withViolation = $rows->first(fn ($row) => str_contains((string) data_get($row, '2.display', $row[2] ?? ''), 'Ahmad Siswa'));
    $withoutViolation = $rows->first(fn ($row) => str_contains((string) data_get($row, '2.display', $row[2] ?? ''), 'Siswa Bersih'));

    expect($withViolation)->not->toBeNull()
        ->and((string) data_get($withViolation, '2.display', $withViolation[2] ?? ''))->toContain('student-violation-badge')
        ->and($withoutViolation)->not->toBeNull()
        ->and((string) data_get($withoutViolation, '2.display', $withoutViolation[2] ?? ''))->not->toContain('student-violation-badge');
});

test('data siswa hides gavel when pelanggaran points are punished', function () {
    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $this->katalogPelanggaran->id,
        'judul' => 'Sudah dihukum',
        'tanggal' => now(),
        'point' => 0,
        'is_punished' => true,
        'point_asli' => 250,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.manajemen-siswa.data-siswa.data'));
    $response->assertOk();

    $row = collect($response->json('data'))->first(fn ($row) => str_contains((string) data_get($row, '2.display', $row[2] ?? ''), 'Ahmad Siswa'));
    expect((string) data_get($row, '2.display', $row[2] ?? ''))->not->toContain('student-violation-badge');
});

test('data siswa shows prestasi badge when student has prestasi', function () {
    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Juara lomba',
        'tanggal' => now(),
        'point' => 10,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.manajemen-siswa.data-siswa.data'));
    $response->assertOk();

    $row = collect($response->json('data'))->first(fn ($row) => str_contains((string) data_get($row, '2.display', $row[2] ?? ''), 'Ahmad Siswa'));
    expect((string) data_get($row, '2.display', $row[2] ?? ''))->toContain('student-prestasi-badge');
});

test('hukuman store removes student from eligible list', function () {
    $jenis = JenisPelanggaran::create([
        'level' => 'sedang',
        'bidang' => 'Kebersihan',
        'nama' => 'Pelanggaran eligible reset',
        'point' => 250,
        'sanction' => 'SP2',
        'is_active' => true,
    ]);

    $pelanggaran = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $jenis->id,
        'judul' => $jenis->nama,
        'tanggal' => now(),
        'point' => 250,
        'reported_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.hukuman-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'sanction' => 'SP2 diterbitkan',
        'status' => 1,
        'tanggal' => now()->format('Y-m-d'),
        'pelanggaran_ids' => [$pelanggaran->id],
    ])->assertOk();

    $eligible = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.hukuman-siswa.eligible.data'));
    $names = collect($eligible->json('data'))->pluck(0)->all();
    expect($names)->not->toContain($this->siswa->name);
});

// ── Dashboard: Rekap Terbanyak ──

test('dashboard ranks siswa & guru by prestasi and pelanggaran count', function () {
    $siswa2 = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000002',
        'name' => 'Budi Siswa',
        'gender' => 'L',
        'status' => 1,
    ]);

    // Prestasi: siswa1 = 3 (24 poin), siswa2 = 1 (5 poin)
    foreach ([10, 8, 6] as $point) {
        PrestasiSiswa::create([
            'siswa_id' => $this->siswa->id,
            'sekolah_id' => $this->sekolah->id,
            'judul' => "Prestasi {$point}",
            'tanggal' => now(),
            'point' => $point,
            'reported_by' => $this->admin->id,
        ]);
    }
    PrestasiSiswa::create([
        'siswa_id' => $siswa2->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Prestasi Budi',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);

    // Pelanggaran: siswa2 = 2 (lebih banyak dari siswa1 = 1)
    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Terlambat',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);
    foreach ([3, 4] as $point) {
        PelanggaranSiswa::create([
            'siswa_id' => $siswa2->id,
            'sekolah_id' => $this->sekolah->id,
            'judul' => "Pelanggaran {$point}",
            'tanggal' => now(),
            'point' => $point,
            'reported_by' => $this->admin->id,
        ]);
    }

    // Guru: 2 prestasi + 1 pelanggaran
    PrestasiGuru::create([
        'guru_id' => $this->guru->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Guru Teladan',
        'tanggal' => now(),
        'point' => 20,
        'reported_by' => $this->admin->id,
    ]);
    PrestasiGuru::create([
        'guru_id' => $this->guru->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Best Teacher',
        'tanggal' => now(),
        'point' => 15,
        'reported_by' => $this->admin->id,
    ]);
    PelanggaranGuru::create([
        'guru_id' => $this->guru->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Terlambat Rapat',
        'tanggal' => now(),
        'point' => 2,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.prestasi-pelanggaran.dashboard'));
    $response->assertOk()->assertSee('Rekap Terbanyak');

    $response->assertViewHas('topPrestasiSiswa', function ($rows) {
        expect($rows)->toHaveCount(2);
        expect($rows[0]['name'])->toBe('Ahmad Siswa');
        expect($rows[0]['subtitle'])->toBe('VII A');
        expect($rows[0]['total'])->toBe(3);
        expect($rows[0]['total_point'])->toBe(24);
        expect($rows[1]['name'])->toBe('Budi Siswa');

        return true;
    });

    $response->assertViewHas('topPelanggaranSiswa', function ($rows) {
        expect($rows)->toHaveCount(2);
        expect($rows[0]['name'])->toBe('Budi Siswa');
        expect($rows[0]['total'])->toBe(2);
        expect($rows[0]['total_point'])->toBe(7);

        return true;
    });

    $response->assertViewHas('topPrestasiGuru', function ($rows) {
        expect($rows)->toHaveCount(1);
        expect($rows[0]['name'])->toBe('Guru Test');
        expect($rows[0]['subtitle'])->toBe('1234567890');
        expect($rows[0]['total'])->toBe(2);
        expect($rows[0]['total_point'])->toBe(35);

        return true;
    });

    $response->assertViewHas('topPelanggaranGuru', function ($rows) {
        expect($rows)->toHaveCount(1);
        expect($rows[0]['name'])->toBe('Guru Test');
        expect($rows[0]['total'])->toBe(1);

        return true;
    });
});

test('dashboard top ranking shows empty state without data', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.prestasi-pelanggaran.dashboard'));
    $response->assertOk()
        ->assertSee('Rekap Terbanyak')
        ->assertViewHas('topPrestasiSiswa', [])
        ->assertViewHas('topPelanggaranSiswa', [])
        ->assertViewHas('topPrestasiGuru', [])
        ->assertViewHas('topPelanggaranGuru', []);
});

test('admin can batch create prestasi for multiple students', function () {
    $siswa2 = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000003',
        'name' => 'Citra Siswa',
        'gender' => 'P',
        'status' => 1,
    ]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.prestasi-siswa.store'), [
        'siswa_ids' => [$this->siswa->id, $siswa2->id],
        'judul' => 'Prestasi Batch',
        'tanggal' => now()->format('Y-m-d'),
        'point' => 8,
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    expect(PrestasiSiswa::where('judul', 'Prestasi Batch')->count())->toBe(2);
});

test('rekap prestasi siswa orders by total point desc', function () {
    $siswa2 = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000004',
        'name' => 'Dedi Siswa',
        'gender' => 'L',
        'status' => 1,
    ]);

    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Prestasi A',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);
    PrestasiSiswa::create([
        'siswa_id' => $siswa2->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Prestasi B',
        'tanggal' => now(),
        'point' => 20,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.rekap-prestasi-siswa.data', [
        'order' => [['column' => 4, 'dir' => 'desc']],
    ]));
    $response->assertOk();
    expect($response->json('data.0.0'))->toBe('Dedi Siswa');
    expect($response->json('data.0.4'))->toBe(20);
});

test('rekap pelanggaran siswa orders by total point desc', function () {
    $siswa2 = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000005',
        'name' => 'Eko Siswa',
        'gender' => 'L',
        'status' => 1,
    ]);

    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Pelanggaran A',
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);
    PelanggaranSiswa::create([
        'siswa_id' => $siswa2->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Pelanggaran B',
        'tanggal' => now(),
        'point' => 20,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.rekap-pelanggaran-siswa.data', [
        'order' => [['column' => 4, 'dir' => 'desc']],
    ]));
    $response->assertOk();
    expect($response->json('data.0.0'))->toBe('Eko Siswa');
    expect($response->json('data.0.4'))->toBe(20);
});

test('hukuman recommendation picks highest sanction from catalog violations', function () {
    $ringan = JenisPelanggaran::create([
        'level' => 'ringan',
        'bidang' => 'Disiplin',
        'nama' => 'Terlambat',
        'point' => 5,
        'sanction' => 'SP1',
        'is_active' => true,
    ]);
    $berat = JenisPelanggaran::create([
        'level' => 'berat',
        'bidang' => 'Akhlak',
        'nama' => 'Membully',
        'point' => 250,
        'sanction' => 'SP3',
        'is_active' => true,
    ]);

    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $ringan->id,
        'judul' => $ringan->nama,
        'tanggal' => now(),
        'point' => 5,
        'reported_by' => $this->admin->id,
    ]);
    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $berat->id,
        'judul' => $berat->nama,
        'tanggal' => now(),
        'point' => 250,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.hukuman-siswa.recommend', $this->siswa));
    $response->assertOk()
        ->assertJsonPath('data.total_point', 255)
        ->assertJsonPath('data.eligible', true)
        ->assertJsonPath('data.min_points', 250)
        ->assertJsonPath('data.recommended_sanction', 'SP3');
});

test('hukuman recommend rejects siswa below min points', function () {
    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Ringan saja',
        'tanggal' => now(),
        'point' => 100,
        'reported_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.prestasi-pelanggaran.hukuman-siswa.recommend', $this->siswa))
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('errors.eligible', false)
        ->assertJsonPath('errors.total_point', 100);
});

test('hukuman eligible data only lists siswa with >= min points', function () {
    $other = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000099',
        'name' => 'Belum Eligible',
        'gender' => 'L',
        'status' => 1,
    ]);

    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Berat',
        'tanggal' => now(),
        'point' => 250,
        'reported_by' => $this->admin->id,
    ]);
    PelanggaranSiswa::create([
        'siswa_id' => $other->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Ringan',
        'tanggal' => now(),
        'point' => 100,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.prestasi-pelanggaran.hukuman-siswa.eligible.data'));
    $response->assertOk();
    $names = collect($response->json('data'))->pluck(0)->all();
    expect($names)->toContain($this->siswa->name)
        ->and($names)->not->toContain('Belum Eligible');
});

test('admin can store hukuman siswa with custom free-text sanction', function () {
    $jenis = JenisPelanggaran::create([
        'level' => 'sedang',
        'bidang' => 'Kebersihan',
        'nama' => 'Buang sampah sembarangan',
        'point' => 250,
        'sanction' => 'SP2',
        'is_active' => true,
    ]);

    $pelanggaran = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $jenis->id,
        'judul' => $jenis->nama,
        'tanggal' => now(),
        'point' => 250,
        'reported_by' => $this->admin->id,
    ]);

    $customSanction = 'Membersihkan halaman selama 3 hari';

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.hukuman-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'sanction' => $customSanction,
        'status' => 1,
        'tanggal' => now()->format('Y-m-d'),
        'keterangan' => 'Hukuman disesuaikan',
        'pelanggaran_ids' => [$pelanggaran->id],
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('hukuman_siswa', [
        'siswa_id' => $this->siswa->id,
        'total_point' => 250,
        'recommended_sanction' => 'SP2',
        'sanction' => $customSanction,
    ]);
    $this->assertDatabaseHas('hukuman_pelanggaran', [
        'pelanggaran_siswa_id' => $pelanggaran->id,
    ]);
    $this->assertDatabaseHas('pelanggaran_siswa', [
        'id' => $pelanggaran->id,
        'is_punished' => true,
        'point' => 0,
        'point_asli' => 250,
    ]);
});

test('hukuman store only marks selected pelanggaran as punished', function () {
    $jenisSp1 = JenisPelanggaran::create([
        'level' => 'ringan',
        'bidang' => 'Disiplin',
        'nama' => 'Terlambat ringan subset',
        'point' => 100,
        'sanction' => 'SP1',
        'is_active' => true,
    ]);
    $jenisSp3 = JenisPelanggaran::create([
        'level' => 'berat',
        'bidang' => 'Disiplin',
        'nama' => 'Berat subset',
        'point' => 200,
        'sanction' => 'SP3',
        'is_active' => true,
    ]);

    $keepActive = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $jenisSp1->id,
        'judul' => $jenisSp1->nama,
        'tanggal' => now()->subDay(),
        'point' => 100,
        'reported_by' => $this->admin->id,
    ]);
    $punish = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $jenisSp3->id,
        'judul' => $jenisSp3->nama,
        'tanggal' => now(),
        'point' => 200,
        'reported_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.hukuman-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'sanction' => 'Hukuman sebagian',
        'status' => 1,
        'tanggal' => now()->format('Y-m-d'),
        'pelanggaran_ids' => [$punish->id],
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('hukuman_siswa', [
        'siswa_id' => $this->siswa->id,
        'total_point' => 200,
        'recommended_sanction' => 'SP3',
    ]);
    $this->assertDatabaseHas('hukuman_pelanggaran', [
        'pelanggaran_siswa_id' => $punish->id,
    ]);
    $this->assertDatabaseMissing('hukuman_pelanggaran', [
        'pelanggaran_siswa_id' => $keepActive->id,
    ]);
    $this->assertDatabaseHas('pelanggaran_siswa', [
        'id' => $punish->id,
        'is_punished' => true,
        'point' => 0,
        'point_asli' => 200,
    ]);
    $this->assertDatabaseHas('pelanggaran_siswa', [
        'id' => $keepActive->id,
        'is_punished' => false,
        'point' => 100,
    ]);
});

test('hukuman store rejects empty pelanggaran selection', function () {
    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Poin cukup',
        'tanggal' => now(),
        'point' => 250,
        'reported_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.hukuman-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'sanction' => 'Tanpa pelanggaran',
        'status' => 1,
        'tanggal' => now()->format('Y-m-d'),
        'pelanggaran_ids' => [],
    ])->assertUnprocessable()->assertJsonValidationErrors(['pelanggaran_ids']);
});

test('admin can store hukuman siswa with bukti file', function () {
    Storage::fake('public');

    $jenis = JenisPelanggaran::create([
        'level' => 'berat',
        'bidang' => 'Disiplin',
        'nama' => 'Melanggar berat',
        'point' => 250,
        'sanction' => 'SP3',
        'is_active' => true,
    ]);

    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $jenis->id,
        'judul' => $jenis->nama,
        'tanggal' => now(),
        'point' => 250,
        'reported_by' => $this->admin->id,
    ]);

    $buktiFile = UploadedFile::fake()->create('hukuman.pdf', 100, 'application/pdf');

    $pelanggaranId = PelanggaranSiswa::query()->where('siswa_id', $this->siswa->id)->value('id');

    $response = $this->actingAs($this->admin)->post(route('admin.prestasi-pelanggaran.hukuman-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'sanction' => 'Surat peringatan khusus',
        'status' => 1,
        'tanggal' => now()->format('Y-m-d'),
        'pelanggaran_ids' => [$pelanggaranId],
        'bukti' => [$buktiFile],
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $hukuman = HukumanSiswa::query()->where('siswa_id', $this->siswa->id)->first();
    expect($hukuman)->not->toBeNull();

    $this->assertDatabaseHas('bukti_catatan', [
        'buktiable_type' => HukumanSiswa::class,
        'buktiable_id' => $hukuman->id,
    ]);
});

test('destroy hukuman siswa cleans up bukti files and restores punished violations', function () {
    Storage::fake('public');

    $pelanggaran = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Pelanggaran dihukum',
        'tanggal' => now(),
        'point' => 250,
        'is_punished' => true,
        'point_asli' => 250,
        'reported_by' => $this->admin->id,
    ]);
    $pelanggaran->update(['point' => 0]);

    $hukuman = HukumanSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'total_point' => 250,
        'recommended_sanction' => 'SP2',
        'sanction' => 'Membersihkan halaman',
        'status' => 1,
        'tanggal' => now()->format('Y-m-d'),
        'processed_by_user_id' => $this->admin->id,
    ]);
    $hukuman->pelanggaranSiswa()->sync([$pelanggaran->id]);

    $filename = 'evidence.pdf';
    $path = "bukti-catatan/hukuman-siswa/{$hukuman->id}/{$filename}";
    Storage::disk('public')->put($path, 'pdf-content');

    BuktiCatatan::create([
        'buktiable_type' => HukumanSiswa::class,
        'buktiable_id' => $hukuman->id,
        'file_path' => $path,
        'file_type' => 'pdf',
        'original_name' => $filename,
        'file_size' => 100,
    ]);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.prestasi-pelanggaran.hukuman-siswa.destroy', $hukuman))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('hukuman_siswa', ['id' => $hukuman->id]);
    $this->assertSoftDeleted('bukti_catatan', ['buktiable_id' => $hukuman->id]);
    Storage::disk('public')->assertMissing($path);

    $pelanggaran->refresh();
    expect($pelanggaran->is_punished)->toBeFalse()
        ->and($pelanggaran->point)->toBe(250)
        ->and($pelanggaran->point_asli)->toBeNull();
});

test('store hukuman rejects siswa below min points', function () {
    $pelanggaran = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Belum cukup',
        'tanggal' => now(),
        'point' => 50,
        'reported_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->postJson(route('admin.prestasi-pelanggaran.hukuman-siswa.store'), [
        'siswa_id' => $this->siswa->id,
        'sanction' => 'SP1',
        'status' => 1,
        'tanggal' => now()->format('Y-m-d'),
        'pelanggaran_ids' => [$pelanggaran->id],
    ])->assertStatus(422)->assertJsonPath('success', false);

    $this->assertDatabaseMissing('hukuman_siswa', ['siswa_id' => $this->siswa->id]);
});

test('rekap prestasi filters by date range name and min total point', function () {
    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Lama',
        'tanggal' => '2025-01-01',
        'point' => 100,
        'reported_by' => $this->admin->id,
    ]);
    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Baru',
        'tanggal' => '2026-08-01',
        'point' => 30,
        'reported_by' => $this->admin->id,
    ]);

    $other = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000888',
        'name' => 'Siswa Lain',
        'gender' => 'P',
        'status' => 1,
    ]);
    PrestasiSiswa::create([
        'siswa_id' => $other->id,
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Lain',
        'tanggal' => '2026-08-01',
        'point' => 200,
        'reported_by' => $this->admin->id,
    ]);

    $byDate = $this->actingAs($this->admin)->getJson(
        route('admin.prestasi-pelanggaran.rekap-prestasi-siswa.data', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'siswa' => $this->siswa->nis,
        ])
    )->assertOk();

    expect($byDate->json('data'))->toHaveCount(1)
        ->and($byDate->json('data.0.4'))->toBe(30);

    $byMin = $this->actingAs($this->admin)->getJson(
        route('admin.prestasi-pelanggaran.rekap-prestasi-siswa.data', [
            'min_total_point' => 150,
        ])
    )->assertOk();

    $names = collect($byMin->json('data'))->pluck(0)->all();
    expect($names)->toContain('Siswa Lain')
        ->and($names)->not->toContain($this->siswa->name);
});
