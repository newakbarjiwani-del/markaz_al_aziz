<?php

use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahfidzHalaqoh;
use App\Models\TahfidzHalaqohAnggota;
use App\Models\TahfidzProgram;
use App\Models\TahfidzRekap;
use App\Models\TahfidzRekapSiswa;
use App\Models\User;
use App\Services\TahfidzRekapComposer;
use App\Support\TahfidzRekapStatus;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'thq',
        'name' => 'Sekolah Halaqoh Test',
        'address' => 'Jl. Test',
    ]);

    $this->admin = User::create([
        'username' => 'admin.halaqoh',
        'name' => 'Admin Halaqoh',
        'email' => 'admin-halaqoh@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->guru = Guru::create([
        'nip' => '198001019999',
        'name' => 'Yumna',
        'jabatan' => 'Ustadzah',
        'sekolah_id' => $this->sekolah->id,
        'status' => 'aktif',
    ]);

    $this->guruUser = User::create([
        'username' => 'guru.yumna',
        'name' => 'Guru Yumna',
        'email' => 'guru-yumna@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'guru_id' => $this->guru->id,
    ]);
    $this->guruUser->assignRole('guru');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '20264011',
        'name' => 'Rifdah Azizah',
        'gender' => 'P',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
});

test('admin can store a scoped halaqoh and open jadwal', function () {
    $program = TahfidzProgram::factory()->create([
        'sekolah_id' => $this->sekolah->id,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.tahfidz.program.store'), [
            'name' => 'ITQON',
            'angkatan' => 6,
            'peserta_label' => 'SANTRIWATI',
            'is_active' => '1',
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.tahfidz.halaqoh.store'), [
            'program_id' => $program->id,
            'guru_id' => $this->guru->id,
        ])
        ->assertCreated();

    expect(TahfidzHalaqoh::query()->where('guru_id', $this->guru->id)->exists())->toBeTrue();

    $this->actingAs($this->admin)
        ->get(route('admin.tahfidz.jadwal.index'))
        ->assertOk()
        ->assertSee('Jadwal Halaqoh', false);
});

test('admin cannot update a halaqoh from another school', function () {
    $otherSchool = Sekolah::create([
        'code' => 'oth',
        'name' => 'Sekolah Lain',
        'address' => 'Jl. Lain',
    ]);
    $otherGuru = Guru::create([
        'nip' => '198001018888',
        'name' => 'Acha',
        'jabatan' => 'Ustadzah',
        'sekolah_id' => $otherSchool->id,
        'status' => 'aktif',
    ]);
    $otherProgram = TahfidzProgram::factory()->create(['sekolah_id' => $otherSchool->id]);
    $otherHalaqoh = TahfidzHalaqoh::factory()->create([
        'program_id' => $otherProgram->id,
        'sekolah_id' => $otherSchool->id,
        'guru_id' => $otherGuru->id,
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.tahfidz.halaqoh.update', $otherHalaqoh), [
            'program_id' => $otherProgram->id,
            'guru_id' => $otherGuru->id,
            'name' => 'Halaqoh Silang',
        ])
        ->assertForbidden();
});

test('rekap composer prints tatsbit murojaah and hadir', function () {
    $program = TahfidzProgram::factory()->create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'ITQON',
        'angkatan' => 6,
        'peserta_label' => 'SANTRIWATI',
    ]);
    $halaqoh = TahfidzHalaqoh::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $this->guru->id,
    ]);
    $rekap = TahfidzRekap::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
        'created_by' => $this->admin->id,
        'status' => TahfidzRekapStatus::SIAP,
    ]);
    TahfidzRekapSiswa::factory()->create([
        'rekap_id' => $rekap->id,
        'halaqoh_id' => $halaqoh->id,
        'siswa_id' => $this->siswa->id,
        'tatsbit_juz' => [1, 2, 4],
        'murojaah_juz' => [6, 7, 8, 9, 10],
        'hadir_hari' => 4,
        'total_juz' => 5,
        'prestasi' => "Tasmi' 5 juz sekali duduk (1-5)",
    ]);

    $message = app(TahfidzRekapComposer::class)->fullMessage($rekap->fresh(['program', 'baris.siswa', 'baris.halaqoh.guru']));

    expect($message)
        ->toContain('REKAP PENCAPAIAN SANTRIWATI ITQON ANGKATAN 6')
        ->toContain('Tatsbit')
        ->toContain('Juz 1,2,4')
        ->toContain("Muroja'ah partner")
        ->toContain('Hadir: 4 hari')
        ->toContain('Kak Rifdah');
});

test('whatsapp build returns wa me url and rejects missing parent phone', function () {
    $program = TahfidzProgram::factory()->create(['sekolah_id' => $this->sekolah->id]);
    $halaqoh = TahfidzHalaqoh::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $this->guru->id,
    ]);
    $rekap = TahfidzRekap::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
        'created_by' => $this->admin->id,
    ]);
    $row = TahfidzRekapSiswa::factory()->create([
        'rekap_id' => $rekap->id,
        'halaqoh_id' => $halaqoh->id,
        'siswa_id' => $this->siswa->id,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.tahfidz.kirim-wa.build'), [
            'rekap_siswa_id' => $row->id,
        ])
        ->assertUnprocessable();

    $ortu = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Ayah Rifdah',
        'telepon_ayah' => '081234567890',
        'status' => 'aktif',
    ]);
    $ortu->siswa()->attach($this->siswa->id);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.tahfidz.kirim-wa.build'), [
            'rekap_siswa_id' => $row->id,
        ]);

    $response->assertOk()->assertJsonPath('success', true);
    expect($response->json('data.whatsapp_url'))->toContain('https://wa.me/6281234567890');
});

test('guru cannot open another ustadzah rekap', function () {
    $otherGuru = Guru::create([
        'nip' => '198001017777',
        'name' => 'Acha',
        'jabatan' => 'Ustadzah',
        'sekolah_id' => $this->sekolah->id,
        'status' => 'aktif',
    ]);
    $program = TahfidzProgram::factory()->create(['sekolah_id' => $this->sekolah->id]);
    TahfidzHalaqoh::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $otherGuru->id,
    ]);
    $rekap = TahfidzRekap::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('portal.guru.tahfidz.rekap.show', $rekap))
        ->assertForbidden();
});

test('guru can fill recap for their own halaqoh member', function () {
    $program = TahfidzProgram::factory()->create(['sekolah_id' => $this->sekolah->id]);
    $halaqoh = TahfidzHalaqoh::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $this->guru->id,
    ]);
    TahfidzHalaqohAnggota::factory()->create([
        'halaqoh_id' => $halaqoh->id,
        'siswa_id' => $this->siswa->id,
        'total_juz' => 5,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('portal.guru.tahfidz.rekap.store'), [
            'program_id' => $program->id,
            'starts_on' => '2026-09-12',
            'ends_on' => '2026-09-17',
        ])
        ->assertRedirect();

    $rekap = TahfidzRekap::query()->first();
    $baris = TahfidzRekapSiswa::query()->first();

    $this->actingAs($this->guruUser)
        ->put(route('portal.guru.tahfidz.rekap.baris.update', [$rekap, $baris]), [
            'tatsbit_juz' => '1,2,4',
            'murojaah_juz' => '6,7,8',
            'hadir_hari' => 4,
            'sakit_hari' => 0,
            'pulang_hari' => 0,
            'total_juz' => 5,
            'prestasi' => "Tasmi' 5 juz sekali duduk (1-5)",
        ])
        ->assertRedirect(route('portal.guru.tahfidz.rekap.show', $rekap));

    expect($baris->fresh()->tatsbit_juz)->toBe([1, 2, 4])
        ->and($baris->fresh()->hadir_hari)->toBe(4);
});

test('ortu can view child weekly recap read-only', function () {
    $program = TahfidzProgram::factory()->create(['sekolah_id' => $this->sekolah->id]);
    $halaqoh = TahfidzHalaqoh::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $this->guru->id,
    ]);
    $rekap = TahfidzRekap::factory()->create([
        'program_id' => $program->id,
        'sekolah_id' => $this->sekolah->id,
    ]);
    TahfidzRekapSiswa::factory()->create([
        'rekap_id' => $rekap->id,
        'halaqoh_id' => $halaqoh->id,
        'siswa_id' => $this->siswa->id,
        'tatsbit_juz' => [1, 2, 4],
    ]);

    $ortu = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Ayah Rifdah',
        'telepon_ayah' => '081234567890',
        'status' => 'aktif',
    ]);
    $ortu->siswa()->attach($this->siswa->id);

    $ortuUser = User::create([
        'username' => 'ortu.rifdah',
        'name' => 'Ortu Rifdah',
        'email' => 'ortu-rifdah@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'orang_tua_id' => $ortu->id,
    ]);
    $ortuUser->assignRole('orang_tua');

    $this->actingAs($ortuUser)
        ->get(route('portal.ortu.tahfidz.index'))
        ->assertOk()
        ->assertSee('Rifdah Azizah', false)
        ->assertSee('Juz 1,2,4', false);
});
