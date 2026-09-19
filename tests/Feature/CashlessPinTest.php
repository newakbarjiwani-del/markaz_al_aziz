<?php

use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\CashlessPin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA PIN Test',
        'address' => 'Jl. PIN',
    ]);

    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'jenjang' => 'X',
        'is_active' => true,
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000901',
        'name' => 'Siswa PIN',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->otherSiswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000902',
        'name' => 'Siswa Lain',
        'gender' => 'P',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->admin = User::create([
        'username' => 'admin.pin',
        'name' => 'Admin PIN',
        'email' => 'admin-pin@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->cashlessUser = User::create([
        'username' => 'cashless.pin',
        'name' => 'Cashless PIN',
        'email' => 'cashless-pin@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->cashlessUser->assignRole('cashless');

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Bapak PIN',
        'nama_ibu' => 'Ibu PIN',
        'status' => 'aktif',
    ]);
    $this->orangTua->siswa()->attach($this->siswa->id);

    $this->ortuUser = User::create([
        'username' => 'ortu.pin',
        'name' => $this->orangTua->displayName(),
        'email' => 'ortu-pin@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'orang_tua_id' => $this->orangTua->id,
    ]);
    $this->ortuUser->assignRole('orang_tua');
});

test('admin can set cashless pin for student', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.dompet-digital.cashless-pin.update', $this->siswa), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->siswa->refresh();
    expect(CashlessPin::isSet($this->siswa->cashless_pin))->toBeTrue();
    expect(CashlessPin::verify('1234', $this->siswa->cashless_pin))->toBeTrue();
});

test('admin can change cashless pin with current pin', function () {
    $this->siswa->forceFill([
        'cashless_pin' => CashlessPin::hash('1234'),
    ])->save();

    $this->actingAs($this->admin)
        ->putJson(route('admin.dompet-digital.cashless-pin.update', $this->siswa), [
            'current_pin' => '1234',
            'pin' => '5678',
            'pin_confirmation' => '5678',
        ])
        ->assertOk();

    expect(CashlessPin::verify('5678', $this->siswa->fresh()->cashless_pin))->toBeTrue();
});

test('admin can reset cashless pin', function () {
    $this->siswa->forceFill([
        'cashless_pin' => CashlessPin::hash('1234'),
    ])->save();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.cashless-pin.reset', $this->siswa))
        ->assertOk()
        ->assertJsonPath('data.pin_set', false);

    expect(CashlessPin::isSet($this->siswa->fresh()->cashless_pin))->toBeFalse();
});

test('cashless role can reset cashless pin', function () {
    $this->siswa->forceFill([
        'cashless_pin' => CashlessPin::hash('1234'),
    ])->save();

    $this->actingAs($this->cashlessUser)
        ->postJson(route('admin.dompet-digital.cashless-pin.reset', $this->siswa))
        ->assertOk()
        ->assertJsonPath('data.pin_set', false);
});

test('parent can open pin cashless page for linked children', function () {
    $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.pin-cashless.index'))
        ->assertOk()
        ->assertSee('PIN Cashless')
        ->assertSee('Siswa PIN');
});

test('parent can set pin for linked child', function () {
    $this->actingAs($this->ortuUser)
        ->putJson(route('portal.ortu.pin-cashless.update', $this->siswa), [
            'pin' => '2468',
            'pin_confirmation' => '2468',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(CashlessPin::verify('2468', $this->siswa->fresh()->cashless_pin))->toBeTrue();
});

test('parent cannot set pin for unlinked child', function () {
    $this->actingAs($this->ortuUser)
        ->putJson(route('portal.ortu.pin-cashless.update', $this->otherSiswa), [
            'pin' => '2468',
            'pin_confirmation' => '2468',
        ])
        ->assertNotFound();
});

test('parent cannot reset cashless pin via admin route', function () {
    $this->siswa->forceFill([
        'cashless_pin' => CashlessPin::hash('1234'),
    ])->save();

    $this->actingAs($this->ortuUser)
        ->postJson(route('admin.dompet-digital.cashless-pin.reset', $this->siswa))
        ->assertForbidden();
});
