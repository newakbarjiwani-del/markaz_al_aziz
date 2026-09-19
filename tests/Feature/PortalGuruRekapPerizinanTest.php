<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Perizinan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalGuruRekapPerizinanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SyncServerRolesSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_portal_guru_can_access_rekap_perizinan_page_and_json(): void
    {
        $sekolah = Sekolah::create(['name' => 'MA', 'code' => 'ma']);
        $guru = Guru::create(['name' => 'Guru Test', 'nip' => '12345678', 'sekolah_id' => $sekolah->id]);
        $user = User::factory()->create(['username' => 'gurutest', 'guru_id' => $guru->id, 'sekolah_id' => $sekolah->id]);
        $user->assignRole('guru');

        $siswa = Siswa::create(['name' => 'Siswa Test', 'nis' => '9999001', 'sekolah_id' => $sekolah->id, 'status' => Siswa::STATUS_ACTIVE]);
        Perizinan::create([
            'siswa_id' => $siswa->id,
            'sekolah_id' => $sekolah->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Izin periksa mata',
            'tgl_mulai' => now(),
            'tgl_sampai' => now()->addHours(2),
            'status' => Perizinan::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($user)->get(route('portal.guru.rekap-perizinan.index'));
        $response->assertStatus(200);
        $response->assertSee('Rekap Perizinan Siswa');

        $jsonResponse = $this->actingAs($user)->get(route('portal.guru.rekap-perizinan.data'));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonPath('recordsTotal', 1);
    }
}
