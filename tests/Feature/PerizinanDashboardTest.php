<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Perizinan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\PerizinanDashboard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerizinanDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_perizinan_dashboard_summary_calculations(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '123456',
            'name' => 'Budi Santri',
            'gender' => 'L',
        ]);

        // Create perizinan records with different statuses
        Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Izin keluar harian',
            'tgl_mulai' => Carbon::now()->subHour(),
            'tgl_sampai' => Carbon::now()->addHours(2),
            'status' => Perizinan::STATUS_DISETUJUI,
        ]);

        Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_PULANG_LIBUR,
            'alasan' => 'Izin libur semester',
            'tgl_mulai' => Carbon::now()->subDays(2),
            'tgl_sampai' => Carbon::now()->subDay(),
            'status' => Perizinan::STATUS_DISETUJUI, // Should count as terlambat since past tgl_sampai and no tgl_kembali_aktual
        ]);

        Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK_PONDOK,
            'alasan' => 'Beli perlengkapan',
            'tgl_mulai' => Carbon::now()->subHours(5),
            'tgl_sampai' => Carbon::now()->subHours(1),
            'tgl_kembali_aktual' => Carbon::now()->subHours(2),
            'status' => Perizinan::STATUS_KEMBALI,
        ]);

        // Summary for Admin
        $summary = PerizinanDashboard::summaryForAdmin();
        $this->assertEquals(3, $summary['total']);
        $this->assertEquals(1, $summary['aktif']);
        $this->assertEquals(1, $summary['terlambat']);
        $this->assertEquals(1, $summary['kembali']);

        // Summary for OrangTua
        $ortuSummary = PerizinanDashboard::summaryForOrangTua([$siswa->id]);
        $this->assertEquals(3, $ortuSummary['total']);
        $this->assertEquals(1, $ortuSummary['aktif']);
    }

    public function test_admin_dashboard_renders_perizinan_rekap(): void
    {
        $admin = User::factory()->create(['username' => 'admin_test_' . uniqid()]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200)
            ->assertSee('Rekap Perizinan Santri');
    }

    public function test_guru_dashboard_renders_perizinan_rekap(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $guru = Guru::create(['sekolah_id' => $sekolah->id, 'nip' => 'G101', 'name' => 'Ustadz Fulan']);
        $guruUser = User::factory()->create([
            'username' => 'guru_dash_' . uniqid(),
            'guru_id' => $guru->id,
            'sekolah_id' => $sekolah->id,
        ]);
        $guruUser->assignRole('guru');

        $response = $this->actingAs($guruUser)->get(route('portal.guru.dashboard'));
        $response->assertStatus(200)
            ->assertSee('Rekap Perizinan Siswa');
    }

    public function test_pimpinan_dashboard_renders_perizinan_rekap(): void
    {
        $pimpinan = User::factory()->create(['username' => 'pimpinan_dash_' . uniqid()]);
        $pimpinan->assignRole('pimpinan');

        $response = $this->actingAs($pimpinan)->get(route('portal.pimpinan.dashboard'));
        $response->assertStatus(200)
            ->assertSee('Rekap Perizinan Pondok &amp; Sekolah', false);
    }

    public function test_ortu_dashboard_renders_perizinan_rekap(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $ortu = OrangTua::create(['name' => 'Wali Santri']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'orang_tua_id' => $ortu->id,
            'nis' => '889001',
            'name' => 'Anak Santri',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $ortuUser = User::factory()->create([
            'username' => 'ortu_dash_' . uniqid(),
            'orang_tua_id' => $ortu->id,
        ]);
        $ortuUser->assignRole('orang_tua');
        $ortu->siswa()->attach($siswa->id);

        Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Ke dokter',
            'tgl_mulai' => Carbon::now()->subHour(),
            'tgl_sampai' => Carbon::now()->addHours(2),
            'status' => Perizinan::STATUS_DISETUJUI,
            'pemberi_izin' => 'Ustadz Fulan',
        ]);

        $response = $this->actingAs($ortuUser)->get(route('portal.ortu.dashboard'));
        $response->assertStatus(200)
            ->assertSee('Rekap Perizinan Anak')
            ->assertSee('Lihat Selengkapnya')
            ->assertSee('Ke dokter')
            ->assertSee('Ustadz Fulan')
            ->assertSee('Sedang Izin');
    }

    public function test_ortu_can_access_dedicated_rekap_perizinan_page(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $ortu = OrangTua::create(['name' => 'Wali Santri 2']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'orang_tua_id' => $ortu->id,
            'nis' => '889002',
            'name' => 'Anak Santri 2',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);
        $ortu->siswa()->attach($siswa->id);

        $ortuUser = User::factory()->create([
            'username' => 'ortu_rekap_' . uniqid(),
            'orang_tua_id' => $ortu->id,
        ]);
        $ortuUser->assignRole('orang_tua');

        $this->actingAs($ortuUser)
            ->get(route('portal.ortu.rekap-perizinan.index'))
            ->assertStatus(200)
            ->assertSee('Rekap Perizinan Anak');

        $this->actingAs($ortuUser)
            ->get(route('portal.ortu.rekap-perizinan.data'))
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    }
}
