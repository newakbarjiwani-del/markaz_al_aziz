<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Rfid;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\RfidResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'SPLIT',
        'name' => 'Split Test',
    ]);
    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X Split',
        'is_active' => true,
    ]);
    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '990001',
        'name' => 'Siswa Split',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    $this->admin = User::create([
        'username' => 'admin.split',
        'name' => 'Admin Split',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('face capture atomically synchronizes row and flag', function () {
    $fotoWajah = 'data:image/jpeg;base64,'.base64_encode(str_repeat('face', 20));

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.rekam-wajah.store', $this->siswa), [
            'foto_wajah' => $fotoWajah,
        ])
        ->assertOk();

    expect($this->siswa->fresh()->hasFotoWajah())->toBeTrue();
    $this->assertDatabaseHas('siswa_wajah', [
        'siswa_id' => $this->siswa->id,
        'foto_wajah' => $fotoWajah,
    ]);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.manajemen-siswa.data-siswa.rekam-wajah.destroy', $this->siswa))
        ->assertOk();

    expect($this->siswa->fresh()->hasFotoWajah())->toBeFalse();
    $this->assertDatabaseMissing('siswa_wajah', ['siswa_id' => $this->siswa->id]);
});

test('rfid resolver distinguishes holders and soft delete frees uid', function () {
    assignRfid($this->siswa, 'GLOBAL-UID-01');

    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'G-SPLIT-01',
        'name' => 'Guru Split',
        'status' => 'aktif',
    ]);
    assignRfid($guru, 'GLOBAL-UID-02');

    $resolver = app(RfidResolver::class);
    expect($resolver->resolveHolder('GLOBAL-UID-01')['type'])->toBe('siswa')
        ->and($resolver->resolveHolder('GLOBAL-UID-02')['type'])->toBe('guru');

    $this->siswa->delete();
    expect(Rfid::where('uid', 'GLOBAL-UID-01')->exists())->toBeFalse();

    assignRfid($guru, 'GLOBAL-UID-01');
    expect($guru->fresh()->rfidUid())->toBe('GLOBAL-UID-01');
});

test('rfid is globally unique and cannot be blocked before assignment', function () {
    assignRfid($this->siswa, 'GLOBAL-UNIQUE-01');

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-guru.data-guru.store'), [
            'nip' => 'G-SPLIT-02',
            'name' => 'Guru Duplicate',
            'status' => 'aktif',
            'rfid_uid' => 'GLOBAL-UNIQUE-01',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('rfid_uid');

    $withoutCard = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '990002',
        'name' => 'Tanpa Kartu',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->actingAs($this->admin)
        ->patchJson(route('admin.dompet-digital.rfid-kontrol.update-block', $withoutCard), [
            'rfid_blocked' => true,
        ])
        ->assertUnprocessable();
});
