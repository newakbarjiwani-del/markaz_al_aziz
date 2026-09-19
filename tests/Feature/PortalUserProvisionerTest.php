<?php

use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PortalUserProvisioner;
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
});

test('siswa portal username uses nis and first name word', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '2025001234',
        'name' => 'Ahmad Fauzi Rahman',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    expect(PortalUserProvisioner::siswaUsernameBase($siswa))->toBe('2025001234_ahmad');

    $user = PortalUserProvisioner::forSiswa($siswa);

    expect($user->username)->toBe('2025001234_ahmad');
    expect($user->hasRole('siswa'))->toBeTrue()
        ->and(Hash::check($user->username, (string) $user->password))->toBeTrue();
});

test('orang tua portal username uses first name word and phone suffix', function () {
    $orangTua = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Budi Santoso',
        'telepon_ayah' => '081234567890',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    expect(PortalUserProvisioner::orangTuaUsernameBase($orangTua))->toBe('budi_7890');

    $user = PortalUserProvisioner::forOrangTua($orangTua);

    expect($user->username)->toBe('budi_7890');
    expect($user->hasRole('orang_tua'))->toBeTrue()
        ->and(Hash::check($user->username, (string) $user->password))->toBeTrue();
});

test('orang tua username falls back to record id when phone is missing', function () {
    $orangTua = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ibu' => 'Siti Aminah',
        'status' => 'aktif',
    ]);

    expect(PortalUserProvisioner::orangTuaUsernameBase($orangTua))->toBe('siti_'.$orangTua->id);
});

test('guru portal username uses full nip slug not digits only', function () {
    $guru = \App\Models\Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'GR-MA-002',
        'name' => 'Ustadz Test',
        'status' => 'aktif',
    ]);

    expect(PortalUserProvisioner::guruUsernameBase($guru))->toBe('gr_ma_002');
});

test('portal username stays unique when base collides', function () {
    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000099',
        'name' => 'Ahmad Ali',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    User::create([
        'username' => '1000001_ahmad',
        'name' => 'Existing',
        'email' => 'existing@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);

    $siswaB = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000001',
        'name' => 'Ahmad Budi',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $user = PortalUserProvisioner::forSiswa($siswaB);

    expect($user->username)->toBe('1000001_ahmad_1');
});

test('portal provisioner reuses existing user for same siswa', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000100',
        'name' => 'Siswa Portal',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $first = PortalUserProvisioner::forSiswa($siswa);
    $second = PortalUserProvisioner::forSiswa($siswa);

    expect($second->id)->toBe($first->id)
        ->and(User::count())->toBe(1);
});
