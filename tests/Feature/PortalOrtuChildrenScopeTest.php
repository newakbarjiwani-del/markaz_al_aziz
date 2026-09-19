<?php

use App\Models\OrangTua;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

/**
 * Regression test: portal orang tua must ALWAYS see every linked child,
 * regardless of the school scope stored on the portal user.
 *
 * `PortalUserProvisioner` copies `orang_tua.sekolah_id` onto `users.sekolah_id`,
 * which makes the global `OperatorSekolahScope` filter `Siswa` (and Tagihan,
 * AbsensiSiswa, etc.) to that one school. A parent with children in multiple
 * schools would then silently lose the children that live outside their school
 * — the "sometimes lists less/more student" bug. Portal roles are identity-scoped
 * (children / self / teaching scope), so `users.sekolah_id` must NOT drive the
 * school operator scope for them.
 */
beforeEach(function () {
    FinanceFixtures::seedPermissions();

    // School A (MA) — default fixture.
    $this->ma = FinanceFixtures::schoolWithStudent();

    // School B (MTs) — a second school with a second child. Avoid creating a
    // second active TahunAkademik or a second global SPP.
    $this->mts = FinanceFixtures::schoolWithStudent([
        'sekolah' => ['code' => 'mts', 'name' => 'MTs Test'],
        'siswa' => ['nis' => '2000099', 'name' => 'Siswa MTs'],
        'tahun' => ['name' => '2024/2025', 'is_active' => false],
        'spp' => ['name' => 'SPP MTs', 'is_spp' => false],
    ]);

    // Parent household scoped to MA (what PortalUserProvisioner copies onto the user).
    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->ma->sekolah->id,
        'nama_ayah' => 'Bapak Lintas',
        'nama_ibu' => 'Ibu Lintas',
        'status' => 'aktif',
    ]);
    $this->orangTua->siswa()->attach([
        $this->ma->siswa->id,
        $this->mts->siswa->id,
    ]);

    // Ortu portal user with users.sekolah_id set to MA (mirrors provisioning).
    $this->ortuUser = User::create([
        'username' => 'ortu.lintas',
        'name' => $this->orangTua->displayName(),
        'email' => 'ortu-lintas@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->ma->sekolah->id,
        'orang_tua_id' => $this->orangTua->id,
    ]);
    $this->ortuUser->assignRole('orang_tua');
});

test('portal ortu Data Anak lists every linked child across schools', function () {
    $response = $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.anak'))
        ->assertOk();

    $response->assertViewHas('children', function ($children) {
        expect($children)->toHaveCount(2);

        return true;
    });

    $response->assertSee('Siswa Test', false)
        ->assertSee('Siswa MTs', false);
});

test('portal ortu tagihan data includes cross-school children', function () {
    // Unpaid tagihan for BOTH children, each in their own school.
    FinanceFixtures::tagihan($this->ma->siswa, $this->ma->spp, $this->ma->tahun);
    FinanceFixtures::tagihan($this->mts->siswa, $this->mts->spp, $this->mts->tahun);

    // Both children's tagihan must appear even though the portal user is
    // school-scoped to MA on users.sekolah_id.
    $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.tagihan.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 2);
});
