<?php

use App\Models\Kamar;
use App\Models\KartuSiswa;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\StatusSantri;
use App\Models\User;
use App\Support\VirtualAccountNumber;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Tigamaya',
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.siswa',
        'name' => 'Admin Siswa',
        'email' => 'admin-siswa@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('manual student create provisions active kartu pelajar', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.store'), [
            'nis' => '2026001001',
            'name' => 'Siswa Baru Manual',
            'kelas_id' => $this->kelas->id,
            'gender' => 'L',
            'status' => 'aktif',
            'address' => 'Jl. Pendidikan No. 10, Pekanbaru',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $siswa = Siswa::where('nis', '2026001001')->first();
    expect($siswa)->not->toBeNull()
        ->and($siswa->address)->toBe('Jl. Pendidikan No. 10, Pekanbaru');

    $kartu = KartuSiswa::where('siswa_id', $siswa->id)->first();
    expect($kartu)->not->toBeNull()
        ->and($kartu->status)->toBe('aktif')
        ->and($kartu->qr_code)->toBe('QR-2026001001');
});

test('kartu pelajar page lists manually created student', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.store'), [
            'nis' => '2026001002',
            'name' => 'Siswa Kartu Gallery',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-siswa.kartu-pelajar'))
        ->assertOk()
        ->assertSee('Siswa Kartu Gallery')
        ->assertSee('2026001002');
});

test('student show page displays kartu pelajar preview', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.store'), [
            'nis' => '2026001003',
            'name' => 'Siswa Show Kartu',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
        ])
        ->assertCreated();

    $siswa = Siswa::where('nis', '2026001003')->firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-siswa.data-siswa.show', $siswa))
        ->assertOk()
        ->assertSee('Kartu Pelajar')
        ->assertSee('Siswa Show Kartu')
        ->assertSee('QR-2026001003')
        ->assertSee('Cetak Kartu')
        ->assertSee('id-card--student', false)
        ->assertDontSee('kartu_depan.png')
        ->assertDontSee('kartu_belakang.jpeg');
});

test('manual student create accepts long production nis', function () {
    $longNis = '512336041084210044';

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.store'), [
            'nis' => $longNis,
            'name' => 'Siswa NIS Panjang',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $siswa = Siswa::where('nis', $longNis)->first();
    expect($siswa)->not->toBeNull()
        ->and($siswa->virtualAccountNumber())->toBe(
            VirtualAccountNumber::fromNis($longNis)
        );
});

test('check va suffix endpoint warns when last ten digits collide', function () {
    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '90000001084210041',
        'name' => 'Collision A',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.check-va-suffix', [
            'nis' => '80000001084210041',
        ]))
        ->assertOk()
        ->assertJsonPath('data.has_collision', true)
        ->assertJsonPath('data.collisions.0.name', 'Collision A');

    expect($response->json('data.warning'))->toContain('Collision A')
        ->and($response->json('data.vano'))->toBe(VirtualAccountNumber::fromNis('80000001084210041'));
});

test('student store persists kamar and status santri', function () {
    $kamar = Kamar::create(['nama' => 'A-01', 'blok' => 'Asrama A', 'is_active' => true]);
    $status = StatusSantri::create(['nama' => 'Santri', 'is_active' => true]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.store'), [
            'nis' => '2026002001',
            'name' => 'Santri Pondok',
            'kelas_id' => $this->kelas->id,
            'kamar_id' => $kamar->id,
            'status_santri_id' => $status->id,
            'gender' => 'L',
            'status' => 'aktif',
        ])
        ->assertCreated();

    $siswa = Siswa::where('nis', '2026002001')->first();
    expect($siswa)->not->toBeNull()
        ->and($siswa->kamar_id)->toBe($kamar->id)
        ->and($siswa->status_santri_id)->toBe($status->id);
});
