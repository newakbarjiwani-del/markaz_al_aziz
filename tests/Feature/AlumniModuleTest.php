<?php

use App\Models\Alumni;
use App\Models\AlumniTracer;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\AlumniTracerStatus;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'alm',
        'name' => 'Sekolah Alumni Test',
        'address' => 'Jl. Test',
    ]);

    $this->admin = User::create([
        'username' => 'admin.alumni',
        'name' => 'Admin Alumni',
        'email' => 'admin-alumni@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('admin can create alumni and tracer response', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.alumni.alumni.store'), [
            'name' => 'Budi Alumni',
            'nis' => '2018001',
            'angkatan' => '2018',
            'sekolah_id' => $this->sekolah->id,
            'email' => 'budi@example.test',
            'is_active' => '1',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $alumni = Alumni::query()->first();
    expect($alumni)->not->toBeNull();

    $this->actingAs($this->admin)
        ->postJson(route('admin.alumni.alumni.tracers.store', $alumni), [
            'tahun_tracer' => '2026',
            'status_lulusan' => AlumniTracerStatus::BEKERJA,
            'institusi' => 'PT Demo',
            'jabatan' => 'Staff',
            'kota' => 'Pekanbaru',
        ])
        ->assertCreated();

    expect(AlumniTracer::query()->count())->toBe(1)
        ->and(AlumniTracer::query()->first()->status_lulusan)->toBe(AlumniTracerStatus::BEKERJA);

    $this->actingAs($this->admin)
        ->get(route('admin.alumni.alumni.show', $alumni))
        ->assertOk()
        ->assertSee('PT Demo');
});

test('public tracer form creates alumni and response', function () {
    $this->get(route('alumni.tracer'))
        ->assertOk()
        ->assertSee('Tracer Study Alumni');

    $this->post(route('alumni.tracer.store'), [
        'name' => 'Siti Alumni',
        'nis' => '2019002',
        'angkatan' => '2019',
        'sekolah_id' => $this->sekolah->id,
        'email' => 'siti@example.test',
        'tahun_tracer' => '2026',
        'status_lulusan' => AlumniTracerStatus::KULIAH,
        'institusi' => 'Universitas Test',
        'jabatan' => 'S1',
    ])->assertRedirect(route('alumni.tracer'));

    expect(Alumni::query()->where('nis', '2019002')->exists())->toBeTrue()
        ->and(AlumniTracer::query()->where('source', AlumniTracer::SOURCE_PUBLIC)->count())->toBe(1);
});

test('admin tracer datatable lists responses', function () {
    $alumni = Alumni::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Tracer List',
        'nis' => '2020003',
        'is_active' => true,
    ]);

    AlumniTracer::create([
        'alumni_id' => $alumni->id,
        'tahun_tracer' => '2026',
        'status_lulusan' => AlumniTracerStatus::WIRAUSAHA,
        'institusi' => 'Usaha Sendiri',
        'submitted_at' => now(),
        'source' => AlumniTracer::SOURCE_ADMIN,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.alumni.tracer.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});
