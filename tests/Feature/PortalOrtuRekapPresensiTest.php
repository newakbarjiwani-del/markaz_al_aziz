<?php

use App\Models\AbsensiSiswa;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\User;
use App\Support\AttendanceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();

    $this->fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '1000701',
            'name' => 'Anak Ortu Rekap',
        ],
    ]);

    $this->otherSiswa = Siswa::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'kelas_id' => $this->fixture->kelas->id,
        'nis' => '1000702',
        'name' => 'Siswa Lain',
        'status' => 1,
    ]);

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'nama_ayah' => 'Bapak Rekap',
        'nama_ibu' => 'Ibu Rekap',
        'status' => 'aktif',
    ]);
    $this->orangTua->siswa()->attach($this->fixture->siswa->id);

    $this->ortuUser = User::create([
        'username' => 'ortu.rekap',
        'name' => $this->orangTua->displayName(),
        'email' => 'ortu-rekap@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'orang_tua_id' => $this->orangTua->id,
    ]);
    $this->ortuUser->assignRole('orang_tua');

    AbsensiSiswa::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'siswa_id' => $this->fixture->siswa->id,
        'date' => now()->toDateString(),
        'status' => AttendanceStatus::HADIR,
        'method' => 'manual',
        'time_in' => '07:00:00',
    ]);

    AbsensiSiswa::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'siswa_id' => $this->otherSiswa->id,
        'date' => now()->toDateString(),
        'status' => AttendanceStatus::ALPHA,
        'method' => 'manual',
    ]);
});

test('parent can open rekap presensi page', function () {
    $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.rekap-presensi.index'))
        ->assertOk()
        ->assertSee('Rekap Presensi Anak');
});

test('parent rekap presensi only includes linked children', function () {
    $response = $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.rekap-presensi.data', [
            'month' => now()->format('Y-m'),
        ]));

    $response->assertOk();
    $html = collect($response->json('data'))->flatten()->implode(' ');

    expect($html)->toContain('Anak Ortu Rekap')
        ->and($html)->toContain('1000701')
        ->and($html)->not->toContain('Siswa Lain')
        ->and($html)->not->toContain('1000702');
});
