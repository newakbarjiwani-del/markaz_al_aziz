<?php

use App\Models\Sekolah;
use App\Models\Siswa;
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

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000400',
        'name' => 'Siswa PWA',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $this->siswaUser = User::create([
        'username' => 'siswa.pwa',
        'name' => 'Siswa PWA',
        'email' => 'siswa-pwa@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'siswa_id' => $this->siswa->id,
    ]);
    $this->siswaUser->assignRole('siswa');

    $this->kantinUser = User::create([
        'username' => 'kantin.pwa',
        'name' => 'Kantin PWA',
        'email' => 'kantin-pwa@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->kantinUser->assignRole('kantin');

    $this->admin = User::create([
        'username' => 'admin.pwa',
        'name' => 'Admin PWA',
        'email' => 'admin-pwa@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('portal dashboard includes pwa manifest and service worker bootstrap', function () {
    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.dashboard'))
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('/portal/manifest.webmanifest', false)
        ->assertSee('data-portal-pwa="1"', false)
        ->assertSee('portal-pwa.js', false);
});

test('kantin dashboard includes pwa manifest', function () {
    $this->actingAs($this->kantinUser)
        ->get(route('portal.kantin.dashboard'))
        ->assertOk()
        ->assertSee('/portal/manifest.webmanifest', false)
        ->assertSee('data-portal-pwa="1"', false);
});

test('portal manifest returns valid json without authentication', function () {
    $this->get('/portal/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('content-type', 'application/manifest+json')
        ->assertJsonPath('display', 'standalone')
        ->assertJsonPath('short_name', config('pwa.short_name'))
        ->assertJsonPath('start_url', '/portal/')
        ->assertJsonPath('scope', '/portal/')
        ->assertJsonPath('id', '/portal/');
});

test('portal entry redirects authenticated portal users to their dashboard', function () {
    $this->actingAs($this->kantinUser)
        ->get(route('portal.entry'))
        ->assertRedirect(route('portal.kantin.dashboard'));
});

test('portal service worker is served without authentication', function () {
    $this->get('/portal/sw.js')
        ->assertOk()
        ->assertHeader('content-type', 'application/javascript; charset=UTF-8')
        ->assertSee('portal-shell-v'.config('pwa.cache_version'), false)
        ->assertSee('/css/app.css', false)
        ->assertSee('Tailwind CDN', false);
});

test('portal entry accepts trailing slash for pwa start url', function () {
    $this->actingAs($this->kantinUser)
        ->get('/portal/')
        ->assertRedirect(route('portal.kantin.dashboard'));
});

test('admin dashboard does not include portal pwa assets', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('rel="manifest"', false)
        ->assertDontSee('data-portal-pwa="1"', false);
});
