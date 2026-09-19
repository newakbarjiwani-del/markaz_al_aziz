<?php

use App\Models\Booklet;
use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');

    $this->sekolah = Sekolah::create([
        'code' => 'bkt',
        'name' => 'Sekolah Booklet Test',
        'address' => 'Jl. Test',
    ]);

    $this->admin = User::create([
        'username' => 'admin.booklet',
        'name' => 'Admin Booklet',
        'email' => 'admin-booklet@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '20263001',
        'name' => 'Siswa Booklet',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->siswaUser = User::create([
        'username' => 'siswa.booklet',
        'name' => 'Siswa Portal Booklet',
        'email' => 'siswa-booklet@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'siswa_id' => $this->siswa->id,
    ]);
    $this->siswaUser->assignRole('siswa');
});

test('admin can create booklet with page and publish', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.booklet.booklet.store'), [
            'title' => 'Majalah Sekolah',
            'summary' => 'Edisi perdana',
            'sekolah_id' => $this->sekolah->id,
            'is_published' => '1',
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $booklet = Booklet::query()->first();
    expect($booklet)->not->toBeNull()
        ->and($booklet->is_published)->toBeTrue()
        ->and($booklet->slug)->not->toBeEmpty();

    $this->actingAs($this->admin)
        ->postJson(route('admin.booklet.booklet.pages.store', $booklet), [
            'title' => 'Kata Pengantar',
            'body' => 'Isi halaman pertama.',
        ])
        ->assertCreated();

    expect($booklet->pages()->count())->toBe(1);

    $this->actingAs($this->admin)
        ->get(route('admin.booklet.booklet.show', $booklet))
        ->assertOk()
        ->assertSee('Kata Pengantar');
});

test('siswa can read published booklet but not draft', function () {
    $published = Booklet::create([
        'sekolah_id' => $this->sekolah->id,
        'title' => 'Booklet Terbit',
        'slug' => 'booklet-terbit',
        'summary' => 'Untuk portal',
        'is_published' => true,
        'published_at' => now(),
    ]);
    $published->pages()->create([
        'sort_order' => 1,
        'title' => 'Halaman 1',
        'body' => 'Konten',
    ]);

    Booklet::create([
        'sekolah_id' => $this->sekolah->id,
        'title' => 'Booklet Draf',
        'slug' => 'booklet-draf',
        'is_published' => false,
    ]);

    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.booklet.index'))
        ->assertOk()
        ->assertSee('Booklet Terbit')
        ->assertDontSee('Booklet Draf');

    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.booklet.show', $published))
        ->assertOk()
        ->assertSee('Halaman 1');
});

test('ortu can open published booklet', function () {
    $booklet = Booklet::create([
        'sekolah_id' => $this->sekolah->id,
        'title' => 'Booklet Ortu',
        'slug' => 'booklet-ortu',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $ortu = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Ortu Booklet',
        'phone' => '08123456780',
    ]);
    $ortu->siswa()->attach($this->siswa->id);

    $ortuUser = User::create([
        'username' => 'ortu.booklet',
        'name' => 'Ortu Portal Booklet',
        'email' => 'ortu-booklet@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'orang_tua_id' => $ortu->id,
    ]);
    $ortuUser->assignRole('orang_tua');

    $this->actingAs($ortuUser)
        ->get(route('portal.ortu.booklet.show', $booklet))
        ->assertOk()
        ->assertSee('Booklet Ortu');
});
