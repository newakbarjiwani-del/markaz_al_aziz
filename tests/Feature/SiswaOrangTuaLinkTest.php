<?php

use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

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
        'username' => 'admin.siswa.ortu',
        'name' => 'Admin Siswa Ortu',
        'email' => 'admin-siswa-ortu@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000101',
        'name' => 'Siswa Contoh',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Ayah Contoh',
        'telepon_ayah' => '081234567890',
        'status' => 'aktif',
    ]);
});

test('data siswa can list linked orang tua', function () {
    $this->siswa->orangTua()->attach($this->orangTua->id);

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.orang-tua.index', $this->siswa))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.siswa.nis', '1000101')
        ->assertJsonPath('data.items.0.id', $this->orangTua->id)
        ->assertJsonPath('data.items.0.name', 'Ayah Contoh');
});

test('data siswa can assign orang tua', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.orang-tua.assign', $this->siswa), [
            'orang_tua_id' => $this->orangTua->id,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->siswa->fresh()->orangTua()->where('orang_tua_id', $this->orangTua->id)->exists())->toBeTrue();
});

test('data siswa assign orang tua rejects duplicate link', function () {
    $this->siswa->orangTua()->attach($this->orangTua->id);

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.orang-tua.assign', $this->siswa), [
            'orang_tua_id' => $this->orangTua->id,
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('data siswa can remove linked orang tua', function () {
    $this->siswa->orangTua()->attach($this->orangTua->id);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.manajemen-siswa.data-siswa.orang-tua.remove', [$this->siswa, $this->orangTua]))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->siswa->fresh()->orangTua()->count())->toBe(0);
});

test('data siswa cannot assign a second different orang tua', function () {
    $other = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ibu' => 'Ibu Lain',
        'telepon_ibu' => '089999999999',
        'status' => 'aktif',
    ]);

    $this->siswa->orangTua()->attach($this->orangTua->id);

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.orang-tua.assign', $this->siswa), [
            'orang_tua_id' => $other->id,
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonFragment(['success' => false]);

    expect($this->siswa->fresh()->orangTua()->pluck('orang_tua.id')->all())->toBe([$this->orangTua->id]);
});

test('orang tua assign siswa rejects student already linked to another parent', function () {
    $other = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_wali' => 'Wali Lain',
        'telepon_wali' => '088888888888',
        'status' => 'aktif',
    ]);

    $this->siswa->orangTua()->attach($this->orangTua->id);

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.orang-tua.assign-siswa', $other), [
            'siswa_id' => $this->siswa->id,
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($this->siswa->fresh()->orangTua()->count())->toBe(1);
});
