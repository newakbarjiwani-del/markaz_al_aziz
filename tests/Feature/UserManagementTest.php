<?php

use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $this->superAdmin = User::create([
        'username' => 'superadmin',
        'name' => 'Super Admin',
        'email' => 'superadmin@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->superAdmin->assignRole('super_admin');
});

test('super admin can create kantin user without siswa link', function () {
    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'kantin_ma',
        'name' => 'Operator Kantin',
        'email' => 'kantin.ma@test.local',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['kantin'],
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $user = User::where('username', 'kantin_ma')->first();
    expect($user)->not->toBeNull()
        ->and($user->siswa_id)->toBeNull()
        ->and($user->hasRole('kantin'))->toBeTrue();
});

test('super admin can create perpustakaan user without siswa link', function () {
    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'perpus_ma',
        'name' => 'Petugas Perpustakaan',
        'email' => 'perpus.ma@test.local',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['perpustakaan'],
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    expect(User::where('username', 'perpus_ma')->first()?->siswa_id)->toBeNull();
});

test('super admin can create pimpinan admin bendahara and cashless users without entity links', function () {
    foreach (['pimpinan', 'admin', 'bendahara', 'cashless'] as $role) {
        $username = $role.'_ma';

        $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
            'username' => $username,
            'name' => ucfirst($role).' User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'aktif',
            'role' => [$role],
            'sekolah_id' => $this->sekolah->id,
        ])->assertCreated();
    }

    expect(User::whereIn('username', ['pimpinan_ma', 'admin_ma', 'bendahara_ma', 'cashless_ma'])->count())->toBe(4);
});

test('siswa role still requires siswa_id on server', function () {
    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'siswa.new',
        'name' => 'Siswa Baru',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['siswa'],
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['siswa_id']);
});

test('user datatable includes linked entity label for guru users', function () {
    $guru = \App\Models\Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'GR-MA-010',
        'name' => 'Guru Portal Label',
        'status' => 'aktif',
    ]);

    User::create([
        'username' => 'gr.ma.010',
        'name' => 'Guru Portal Label',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $guru->id,
    ])->assignRole('guru');

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('super-admin.users.data'));

    $response->assertOk();

    $data = collect($response->json('data'));
    $userRow = $data->first(fn ($row) => collect($row)->contains('gr.ma.010'));
    expect($userRow)->not->toBeNull();

    $actionCell = collect($userRow)->first(fn ($cell) => is_array($cell) && data_get($cell, 'type') === 'action');
    expect($actionCell)->not->toBeNull();

    $record = data_get($actionCell, 'raw.actions.edit.record', []);
    expect(isset($record['linked_entity_label']))->toBeTrue()
        ->and($record['linked_entity_label'])->toContain('GR-MA-010')
        ->and($record['linked_entity_label'])->toContain('Guru Portal Label');
});

test('super admin cannot delete own account', function () {
    $this->actingAs($this->superAdmin)
        ->deleteJson(route('super-admin.users.destroy', $this->superAdmin))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tidak dapat menghapus akun yang sedang login.');

    expect(User::find($this->superAdmin->id))->not->toBeNull();
});

test('user datatable hides delete action for logged in user', function () {
    User::create([
        'username' => 'other_admin',
        'name' => 'Other Admin',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ])->assignRole('admin');

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('super-admin.users.data'));

    $response->assertOk();

    $rows = collect($response->json('data'));
    $rowText = static fn (array $row): string => collect($row)
        ->map(fn ($cell) => is_scalar($cell) ? (string) $cell : '')
        ->implode(' ');

    $selfRow = $rows->first(fn (array $row) => str_contains($rowText($row), 'superadmin'));
    $otherRow = $rows->first(fn (array $row) => str_contains($rowText($row), 'other_admin'));

    $selfActionCell = collect($selfRow ?? [])->first(fn ($cell) => is_array($cell) && ($cell['type'] ?? null) === 'action');
    $otherActionCell = collect($otherRow ?? [])->first(fn ($cell) => is_array($cell) && ($cell['type'] ?? null) === 'action');

    expect($selfRow)->not->toBeNull()
        ->and($selfActionCell)->not->toBeNull()
        ->and(data_get($selfActionCell, 'raw.actions.delete'))->toBeNull()
        ->and($otherRow)->not->toBeNull()
        ->and($otherActionCell)->not->toBeNull()
        ->and(data_get($otherActionCell, 'raw.actions.delete.url'))->not->toBeEmpty();
});

test('super admin must confirm current password to update own account', function () {
    $this->actingAs($this->superAdmin)
        ->putJson(route('super-admin.users.update', $this->superAdmin), [
            'username' => 'superadmin',
            'name' => 'Super Admin Updated',
            'email' => 'superadmin@test.local',
            'status' => 'aktif',
            'role' => ['super_admin'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

test('super admin can update own account with current password', function () {
    $this->actingAs($this->superAdmin)
        ->putJson(route('super-admin.users.update', $this->superAdmin), [
            'username' => 'superadmin',
            'name' => 'Super Admin Updated',
            'email' => 'superadmin@test.local',
            'status' => 'aktif',
            'role' => ['super_admin'],
            'current_password' => 'password',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->superAdmin->fresh()->name)->toBe('Super Admin Updated');
});

test('super admin can update other user without current password', function () {
    $other = User::create([
        'username' => 'admin_ops',
        'name' => 'Admin Ops',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $other->assignRole('admin');

    $this->actingAs($this->superAdmin)
        ->putJson(route('super-admin.users.update', $other), [
            'username' => 'admin_ops',
            'name' => 'Admin Ops Updated',
            'status' => 'aktif',
            'role' => ['admin'],
            'sekolah_id' => $this->sekolah->id,
        ])
        ->assertOk();

    expect($other->fresh()->name)->toBe('Admin Ops Updated');
});

test('super admin can reset siswa password to username', function () {
    $siswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000400',
        'name' => 'Siswa Reset',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $user = User::create([
        'username' => 'siswa_reset_1',
        'name' => 'Siswa Reset User',
        'password' => Hash::make('OldPassword123!'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'siswa_id' => $siswa->id,
    ]);
    $user->assignRole('siswa');

    $this->actingAs($this->superAdmin)
        ->postJson(route('super-admin.users.reset-password', $user))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Hash::check('siswa_reset_1', (string) $user->fresh()->password))->toBeTrue();
});

test('super admin cannot reset password of another super admin', function () {
    $other = User::create([
        'username' => 'superadmin2',
        'name' => 'Other Super Admin',
        'email' => 'superadmin2@test.local',
        'password' => Hash::make('OldPassword123!'),
        'status' => 'aktif',
    ]);
    $other->assignRole('super_admin');

    $this->actingAs($this->superAdmin)
        ->postJson(route('super-admin.users.reset-password', $other))
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tidak dapat reset password akun super admin lain.');

    expect(Hash::check('OldPassword123!', (string) $other->fresh()->password))->toBeTrue();
});

test('user datatable hides reset action for other super admin', function () {
    User::create([
        'username' => 'superadmin2',
        'name' => 'Other Super Admin',
        'email' => 'superadmin2@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ])->assignRole('super_admin');

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('super-admin.users.data'));

    $response->assertOk();

    $rows = collect($response->json('data'));
    $rowText = static fn (array $row): string => collect($row)
        ->map(fn ($cell) => is_scalar($cell) ? (string) $cell : '')
        ->implode(' ');

    $otherRow = $rows->first(fn (array $row) => str_contains($rowText($row), 'superadmin2'));
    $otherActionCell = collect($otherRow ?? [])->first(fn ($cell) => is_array($cell) && ($cell['type'] ?? null) === 'action');

    expect($otherRow)->not->toBeNull()
        ->and($otherActionCell)->not->toBeNull()
        ->and(data_get($otherActionCell, 'raw.actions.reset'))->toBeNull()
        ->and(data_get($otherActionCell, 'raw.actions.edit'))->not->toBeNull()
        ->and(data_get($otherActionCell, 'raw.actions.edit.can_change_password'))->toBeFalse()
        ->and(data_get($otherActionCell, 'raw.actions.edit.edit_self'))->toBeFalse();
});

test('super admin cannot change password of another super admin via update', function () {
    $other = User::create([
        'username' => 'superadmin2',
        'name' => 'Other Super Admin',
        'email' => 'superadmin2@test.local',
        'password' => Hash::make('OldPassword123!'),
        'status' => 'aktif',
    ]);
    $other->assignRole('super_admin');

    $this->actingAs($this->superAdmin)
        ->putJson(route('super-admin.users.update', $other), [
            'username' => 'superadmin2',
            'name' => 'Other Super Admin',
            'email' => 'superadmin2@test.local',
            'status' => 'aktif',
            'role' => ['super_admin'],
            'password' => 'HackPassword123!',
            'password_confirmation' => 'HackPassword123!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);

    expect(Hash::check('OldPassword123!', (string) $other->fresh()->password))->toBeTrue();
});

test('super admin can update other super admin profile without password', function () {
    $other = User::create([
        'username' => 'superadmin2',
        'name' => 'Other Super Admin',
        'email' => 'superadmin2@test.local',
        'password' => Hash::make('OldPassword123!'),
        'status' => 'aktif',
    ]);
    $other->assignRole('super_admin');

    $this->actingAs($this->superAdmin)
        ->putJson(route('super-admin.users.update', $other), [
            'username' => 'superadmin2',
            'name' => 'Other Super Admin Updated',
            'email' => 'superadmin2@test.local',
            'status' => 'aktif',
            'role' => ['super_admin'],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($other->fresh()->name)->toBe('Other Super Admin Updated')
        ->and(Hash::check('OldPassword123!', (string) $other->fresh()->password))->toBeTrue();
});

test('admin can view manage users data for siswa orang tua and guru only in own school', function () {
    $admin = User::create([
        'username' => 'admin.viewer',
        'name' => 'Admin Viewer',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $admin->assignRole('admin');

    $otherSchool = Sekolah::create([
        'code' => 'mts',
        'name' => 'MTs Test',
        'address' => 'Jl. Mts',
    ]);

    User::create([
        'username' => 'siswa_ma',
        'name' => 'Siswa MA',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ])->assignRole('siswa');
    User::create([
        'username' => 'guru_ma',
        'name' => 'Guru MA',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ])->assignRole('guru');
    User::create([
        'username' => 'ortu_ma',
        'name' => 'Ortu MA',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ])->assignRole('orang_tua');
    User::create([
        'username' => 'kantin_ma',
        'name' => 'Kantin MA',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ])->assignRole('kantin');
    User::create([
        'username' => 'siswa_mts',
        'name' => 'Siswa MTs',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $otherSchool->id,
    ])->assignRole('siswa');

    $this->actingAs($admin)
        ->get(route('admin.manajemen-user.index'))
        ->assertOk();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.manajemen-user.data'))
        ->assertOk();

    $rowsText = collect($response->json('data'))
        ->map(fn (array $row) => implode(' ', array_map(fn ($cell) => is_scalar($cell) ? (string) $cell : '', $row)))
        ->implode("\n");

    expect($rowsText)->toContain('siswa_ma')
        ->toContain('guru_ma')
        ->toContain('ortu_ma')
        ->not->toContain('kantin_ma')
        ->not->toContain('siswa_mts');
});

test('admin can update and reset password for siswa user in same school', function () {
    $admin = User::create([
        'username' => 'admin.editor',
        'name' => 'Admin Editor',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $admin->assignRole('admin');

    $siswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000900',
        'name' => 'Siswa Editable',
        'gender' => 'L',
        'status' => 'aktif',
    ]);

    $siswaUser = User::create([
        'username' => 'siswa_editable',
        'name' => 'Siswa Editable',
        'password' => Hash::make('OldPassword123!'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'siswa_id' => $siswa->id,
    ]);
    $siswaUser->assignRole('siswa');

    $this->actingAs($admin)
        ->putJson(route('admin.manajemen-user.update', $siswaUser), [
            'username' => 'siswa_editable',
            'name' => 'Siswa Edited',
            'email' => null,
            'phone' => '081200000001',
            'status' => 'aktif',
            'role' => ['siswa'],
            'sekolah_id' => $this->sekolah->id,
            'siswa_id' => $siswa->id,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($siswaUser->fresh()->name)->toBe('Siswa Edited');

    $this->actingAs($admin)
        ->postJson(route('admin.manajemen-user.reset-password', $siswaUser))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Hash::check('siswa_editable', (string) $siswaUser->fresh()->password))->toBeTrue();
});

test('admin cannot update or reset non portal role users', function () {
    $admin = User::create([
        'username' => 'admin.limited',
        'name' => 'Admin Limited',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $admin->assignRole('admin');

    $kantinUser = User::create([
        'username' => 'kantin_limited',
        'name' => 'Kantin Limited',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantinUser->assignRole('kantin');

    $this->actingAs($admin)
        ->putJson(route('admin.manajemen-user.update', $kantinUser), [
            'username' => 'kantin_limited',
            'name' => 'Kantin Edited',
            'status' => 'aktif',
            'role' => ['kantin'],
            'sekolah_id' => $this->sekolah->id,
        ])
        ->assertUnprocessable();

    $this->actingAs($admin)
        ->postJson(route('admin.manajemen-user.reset-password', $kantinUser))
        ->assertUnprocessable();
});

test('self update requires password confirmation when changing password', function () {
    $this->actingAs($this->superAdmin)
        ->putJson(route('super-admin.users.update', $this->superAdmin), [
            'username' => 'superadmin',
            'name' => 'Super Admin',
            'status' => 'aktif',
            'role' => ['super_admin'],
            'current_password' => 'password',
            'password' => 'NewPassword123!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('super admin cannot create duplicate siswa user for same entity', function () {
    $siswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000200',
        'name' => 'Siswa Duplikat',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    User::create([
        'username' => 'siswa_portal',
        'name' => 'Siswa Portal',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'siswa_id' => $siswa->id,
    ])->assignRole('siswa');

    $this->actingAs($this->superAdmin)
        ->postJson(route('super-admin.users.store'), [
            'username' => 'siswa_portal_2',
            'name' => 'Siswa Portal Duplikat',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'aktif',
            'role' => ['siswa'],
            'sekolah_id' => $this->sekolah->id,
            'siswa_id' => $siswa->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['siswa_id']);
});

test('super admin cannot create duplicate guru user for same entity', function () {
    $guru = \App\Models\Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => '198501019999',
        'name' => 'Guru Duplikat',
        'status' => 'aktif',
    ]);

    User::create([
        'username' => 'guru_portal',
        'name' => 'Guru Portal',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $guru->id,
    ])->assignRole('guru');

    $this->actingAs($this->superAdmin)
        ->postJson(route('super-admin.users.store'), [
            'username' => 'guru_portal_2',
            'name' => 'Guru Portal Duplikat',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'aktif',
            'role' => ['guru'],
            'sekolah_id' => $this->sekolah->id,
            'guru_id' => $guru->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['guru_id']);
});

test('entity link can be reused after previous user is soft deleted', function () {
    $siswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000300',
        'name' => 'Siswa Reuse',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $oldUser = User::create([
        'username' => 'siswa_lama',
        'name' => 'Siswa Lama',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'siswa_id' => $siswa->id,
    ]);
    $oldUser->assignRole('siswa');
    $oldUser->delete();

    $this->actingAs($this->superAdmin)
        ->postJson(route('super-admin.users.store'), [
            'username' => 'siswa_baru',
            'name' => 'Siswa Baru',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'aktif',
            'role' => ['siswa'],
            'sekolah_id' => $this->sekolah->id,
            'siswa_id' => $siswa->id,
        ])
        ->assertCreated();

    expect(User::where('siswa_id', $siswa->id)->count())->toBe(1);
});
