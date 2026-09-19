<?php

use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahfidzProgress;
use App\Models\TahfidzSurah;
use App\Models\TahfidzTarget;
use App\Models\User;
use App\Support\TahfidzProgressStatus;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TahfidzQuranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(TahfidzQuranSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'thf',
        'name' => 'Sekolah Tahfidz Test',
        'address' => 'Jl. Test',
    ]);

    $this->admin = User::create([
        'username' => 'admin.tahfidz',
        'name' => 'Admin Tahfidz',
        'email' => 'admin-tahfidz@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '20264001',
        'name' => 'Siswa Tahfidz',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->siswaUser = User::create([
        'username' => 'siswa.tahfidz',
        'name' => 'Siswa Portal Tahfidz',
        'email' => 'siswa-tahfidz@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'siswa_id' => $this->siswa->id,
    ]);
    $this->siswaUser->assignRole('siswa');

    $this->fatihah = TahfidzSurah::query()->where('number', 1)->firstOrFail();
});

test('admin can open tahfidz dashboard and lists', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.tahfidz.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Tahfidz', false);

    $this->actingAs($this->admin)
        ->get(route('admin.tahfidz.progress.index'))
        ->assertOk();

    $this->actingAs($this->admin)
        ->get(route('admin.tahfidz.target.index'))
        ->assertOk();
});

test('admin can store target and progress', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.tahfidz.target.store'), [
            'siswa_id' => $this->siswa->id,
            'sekolah_id' => $this->sekolah->id,
            'range_type' => 'ayat',
            'surah_id' => $this->fatihah->id,
            'ayah_from' => 1,
            'ayah_to' => 7,
            'period' => 'weekly',
            'due_date' => now()->addWeek()->toDateString(),
            'note' => 'Hafalkan Fatihah',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    expect(TahfidzTarget::query()->count())->toBe(1);

    $this->actingAs($this->admin)
        ->postJson(route('admin.tahfidz.progress.store'), [
            'siswa_id' => $this->siswa->id,
            'sekolah_id' => $this->sekolah->id,
            'surah_id' => $this->fatihah->id,
            'ayah_from' => 1,
            'ayah_to' => 7,
            'status' => TahfidzProgressStatus::PROSES,
            'note' => 'Mulai murajaah',
        ])
        ->assertCreated();

    $progress = TahfidzProgress::query()->first();
    expect($progress)->not->toBeNull()
        ->and($progress->status)->toBe(TahfidzProgressStatus::PROSES)
        ->and($progress->murajaahLogs()->count())->toBe(1);
});

test('siswa can read mushaf and store progress', function () {
    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.tahfidz.index'))
        ->assertOk()
        ->assertSee('Al-Fatihah', false);

    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.tahfidz.surah', $this->fatihah))
        ->assertOk()
        ->assertSee('بِسْمِ', false);

    $this->actingAs($this->siswaUser)
        ->post(route('portal.siswa.tahfidz.progress.store'), [
            'surah_id' => $this->fatihah->id,
            'ayah_from' => 1,
            'ayah_to' => 3,
            'status' => TahfidzProgressStatus::LANCAR,
            'note' => 'Mandiri',
        ])
        ->assertRedirect(route('portal.siswa.tahfidz.index'));

    expect(TahfidzProgress::query()->where('siswa_id', $this->siswa->id)->exists())->toBeTrue();
});

test('siswa can open juz mushaf when fixture has ayahs', function () {
    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.tahfidz.juz', 30))
        ->assertOk()
        ->assertSee('Juz 30', false);
});

test('guru can verify progress', function () {
    $guru = Guru::create([
        'nip' => '198001012000',
        'name' => 'Guru Tahfidz',
        'jabatan' => 'Guru Tahfidz',
        'sekolah_id' => $this->sekolah->id,
        'status' => 'aktif',
    ]);

    $guruUser = User::create([
        'username' => 'guru.tahfidz',
        'name' => 'Guru Portal Tahfidz',
        'email' => 'guru-tahfidz@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'guru_id' => $guru->id,
    ]);
    $guruUser->assignRole('guru');

    $this->actingAs($guruUser)
        ->post(route('portal.guru.tahfidz.progress.store'), [
            'siswa_id' => $this->siswa->id,
            'surah_id' => $this->fatihah->id,
            'ayah_from' => 1,
            'ayah_to' => 7,
            'status' => TahfidzProgressStatus::MUTQIN,
            'note' => 'Lulus',
            'verified' => '1',
        ])
        ->assertRedirect(route('portal.guru.tahfidz.index'));

    $progress = TahfidzProgress::query()->first();
    expect($progress?->status)->toBe(TahfidzProgressStatus::MUTQIN)
        ->and($progress?->verified_by)->toBe($guruUser->id);
});

test('ortu can view child progress read-only', function () {
    TahfidzProgress::query()->create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'surah_id' => $this->fatihah->id,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'status' => TahfidzProgressStatus::PROSES,
        'last_reviewed_at' => now(),
    ]);

    $ortu = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Ortu Tahfidz',
        'phone' => '081234567890',
    ]);
    $ortu->siswa()->attach($this->siswa->id);

    $ortuUser = User::create([
        'username' => 'ortu.tahfidz',
        'name' => 'Ortu Portal Tahfidz',
        'email' => 'ortu-tahfidz@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'orang_tua_id' => $ortu->id,
    ]);
    $ortuUser->assignRole('orang_tua');

    $this->actingAs($ortuUser)
        ->get(route('portal.ortu.tahfidz.index'))
        ->assertOk()
        ->assertSee('Siswa Tahfidz', false)
        ->assertSee('Al-Fatihah', false);
});

test('siswa cannot open admin tahfidz dashboard', function () {
    $this->actingAs($this->siswaUser)
        ->get(route('admin.tahfidz.dashboard'))
        ->assertForbidden();
});
