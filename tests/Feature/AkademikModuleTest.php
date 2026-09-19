<?php

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiEntry;
use App\Models\OrangTua;
use App\Models\Rapor;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Support\AkademikSemester;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'akd',
        'name' => 'Sekolah Akademik Test',
        'address' => 'Jl. Test',
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'VII A',
        'unit' => 'SMP',
        'is_active' => true,
    ]);

    $this->tahun = TahunAkademik::create([
        'name' => '2026/2027',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.akademik',
        'name' => 'Admin Akademik',
        'email' => 'admin-akademik@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '20261001',
        'name' => 'Siswa Akademik',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->mapel = MataPelajaran::create([
        'sekolah_id' => $this->sekolah->id,
        'code' => 'MTK',
        'name' => 'Matematika',
        'kelompok' => 'Umum',
        'is_active' => true,
    ]);
});

test('admin akademik dashboard and mata pelajaran crud work', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.akademik.dashboard'))
        ->assertOk()
        ->assertSee('Ringkasan Akademik');

    $this->actingAs($this->admin)
        ->postJson(route('admin.akademik.mata-pelajaran.store'), [
            'code' => 'BIN',
            'name' => 'Bahasa Indonesia',
            'kelompok' => 'Umum',
            'is_active' => '1',
            'sekolah_id' => $this->sekolah->id,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    expect(MataPelajaran::query()->where('code', 'BIN')->exists())->toBeTrue();

    $this->actingAs($this->admin)
        ->getJson(route('admin.akademik.mata-pelajaran.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 2);
});

test('admin can store nilai build draft rapor and finalize', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.akademik.nilai.store'), [
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_akademik_id' => $this->tahun->id,
            'semester' => AkademikSemester::GANJIL,
            'jenis' => NilaiEntry::JENIS_UTS,
            'skor' => 88,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.akademik.rapor.build'), [
            'siswa_id' => $this->siswa->id,
            'tahun_akademik_id' => $this->tahun->id,
            'semester' => AkademikSemester::GANJIL,
            'catatan_wali' => 'Pertahankan',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $rapor = Rapor::query()->first();
    expect($rapor)->not->toBeNull()
        ->and($rapor->status)->toBe(Rapor::STATUS_DRAFT)
        ->and($rapor->mapel)->toHaveCount(1)
        ->and((float) $rapor->mapel->first()->nilai_akhir)->toBe(88.0);

    $this->actingAs($this->admin)
        ->postJson(route('admin.akademik.rapor.finalize', $rapor))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($rapor->fresh()->isFinal())->toBeTrue();
});

test('siswa and ortu can view finalized rapor portal pages', function () {
    $rapor = Rapor::create([
        'siswa_id' => $this->siswa->id,
        'tahun_akademik_id' => $this->tahun->id,
        'semester' => AkademikSemester::GENAP,
        'status' => Rapor::STATUS_FINAL,
        'finalized_at' => now(),
    ]);

    $siswaUser = User::create([
        'username' => 'siswa.akademik',
        'name' => 'Siswa Portal',
        'email' => 'siswa-akademik@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'siswa_id' => $this->siswa->id,
    ]);
    $siswaUser->assignRole('siswa');

    $this->actingAs($siswaUser)
        ->get(route('portal.siswa.rapor.index'))
        ->assertOk()
        ->assertSee('Rapor Saya')
        ->assertSee($this->tahun->name);

    $ortu = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Ortu Akademik',
        'phone' => '08123456789',
    ]);
    $ortu->siswa()->attach($this->siswa->id);

    $ortuUser = User::create([
        'username' => 'ortu.akademik',
        'name' => 'Ortu Portal',
        'email' => 'ortu-akademik@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'orang_tua_id' => $ortu->id,
    ]);
    $ortuUser->assignRole('orang_tua');

    $this->actingAs($ortuUser)
        ->get(route('portal.ortu.rapor.index'))
        ->assertOk()
        ->assertSee('Rapor Anak')
        ->assertSee($this->siswa->name);

    expect($rapor->fresh()->status)->toBe(Rapor::STATUS_FINAL);
});

test('laporan nilai data aggregates skor', function () {
    NilaiEntry::create([
        'siswa_id' => $this->siswa->id,
        'mata_pelajaran_id' => $this->mapel->id,
        'tahun_akademik_id' => $this->tahun->id,
        'semester' => AkademikSemester::GANJIL,
        'jenis' => NilaiEntry::JENIS_HARIAN,
        'skor' => 80,
        'recorded_by' => $this->admin->id,
    ]);
    NilaiEntry::create([
        'siswa_id' => $this->siswa->id,
        'mata_pelajaran_id' => $this->mapel->id,
        'tahun_akademik_id' => $this->tahun->id,
        'semester' => AkademikSemester::GANJIL,
        'jenis' => NilaiEntry::JENIS_UTS,
        'skor' => 90,
        'recorded_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.akademik.laporan-nilai.data', [
            'tahun_akademik_id' => $this->tahun->id,
            'semester' => AkademikSemester::GANJIL,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});
