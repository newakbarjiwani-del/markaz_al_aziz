<?php

namespace Tests\Feature;

use App\Models\Perizinan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalPerizinanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_with_perizinan_role_can_view_portal_perizinan_pages(): void
    {
        $user = User::factory()->create(['username' => 'security_' . uniqid()]);
        $user->assignRole('perizinan');

        $this->actingAs($user)
            ->get(route('portal.perizinan.dashboard'))
            ->assertStatus(200)
            ->assertSee('Dashboard Perizinan');

        $this->actingAs($user)
            ->get(route('portal.perizinan.keluar-masuk.index'))
            ->assertStatus(200)
            ->assertSee('Izin Keluar Masuk (Sekolah)');

        $this->actingAs($user)
            ->get(route('portal.perizinan.keluar-masuk-pondok.index'))
            ->assertStatus(200)
            ->assertSee('Izin Keluar Masuk Pondok (Asrama)');

        $this->actingAs($user)
            ->get(route('portal.perizinan.pulang-libur.index'))
            ->assertStatus(200)
            ->assertSee('Izin Pulang Libur (Mudik Santri)');

        $this->actingAs($user)
            ->get(route('portal.perizinan.rekap-laporan'))
            ->assertStatus(200)
            ->assertSee('Rekap', false);
    }

    public function test_teacher_with_perizinan_role_can_store_and_checkin_perizinan(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '990011',
            'name' => 'Ahmad Santri',
            'gender' => 'L',
        ]);

        $guruUser = User::factory()->create(['username' => 'guru_perizinan_' . uniqid()]);
        $guruUser->assignRole(['guru', 'perizinan']);

        $tglMulai = Carbon::now()->format('Y-m-d\TH:i');
        $tglSampai = Carbon::now()->addHours(4)->format('Y-m-d\TH:i');

        $response = $this->actingAs($guruUser)
            ->postJson(route('portal.perizinan.keluar-masuk.store'), [
                'siswa_id' => $siswa->id,
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
                'alasan' => 'Izin periksa ke klinik',
                'tgl_mulai' => $tglMulai,
                'tgl_sampai' => $tglSampai,
                'penanggung_jawab' => 'Guru Piket',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('perizinan', [
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Izin periksa ke klinik',
        ]);

        $perizinan = Perizinan::where('siswa_id', $siswa->id)->firstOrFail();

        // Checkin test
        $checkinResponse = $this->actingAs($guruUser)
            ->postJson(route('portal.perizinan.keluar-masuk.checkin', $perizinan));

        $checkinResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $perizinan->refresh();
        $this->assertEquals(Perizinan::STATUS_KEMBALI, $perizinan->status);
        $this->assertNotNull($perizinan->tgl_kembali_aktual);
    }

    public function test_user_with_sekolah_id_only_sees_their_school_students_in_lookup(): void
    {
        $sekolah1 = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $sekolah2 = Sekolah::create(['code' => 'mts', 'name' => 'MTs']);

        $siswaMa = Siswa::create([
            'sekolah_id' => $sekolah1->id,
            'nis' => '1000005',
            'name' => 'Jindra Rahimah MA',
            'gender' => 'L',
        ]);
        $siswaMts = Siswa::create([
            'sekolah_id' => $sekolah2->id,
            'nis' => '1000006',
            'name' => 'Jindra Rahimah MTs',
            'gender' => 'L',
        ]);

        // Guru scoped to MA
        $guruMa = User::factory()->create([
            'username' => 'guru_ma_' . uniqid(),
            'sekolah_id' => $sekolah1->id,
        ]);
        $guruMa->assignRole(['guru', 'perizinan']);

        $response = $this->actingAs($guruMa)
            ->getJson(route('admin.siswa.lookup', ['q' => 'jin']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $siswaMa->id);
    }

    public function test_user_without_sekolah_id_sees_students_from_all_schools_in_lookup(): void
    {
        $sekolah1 = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $sekolah2 = Sekolah::create(['code' => 'mts', 'name' => 'MTs']);

        $siswaMa = Siswa::create([
            'sekolah_id' => $sekolah1->id,
            'nis' => '1000005',
            'name' => 'Jindra Rahimah MA',
            'gender' => 'L',
        ]);
        $siswaMts = Siswa::create([
            'sekolah_id' => $sekolah2->id,
            'nis' => '1000006',
            'name' => 'Jindra Rahimah MTs',
            'gender' => 'L',
        ]);

        // Guru unassigned (sekolah_id = null)
        $guruUnassigned = User::factory()->create([
            'username' => 'guru_all_' . uniqid(),
            'sekolah_id' => null,
        ]);
        $guruUnassigned->assignRole(['guru', 'perizinan']);

        $response = $this->actingAs($guruUnassigned)
            ->getJson(route('admin.siswa.lookup', ['q' => 'jin']));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'results');
    }

    public function test_unauthorized_user_cannot_access_portal_perizinan(): void
    {
        $regularSiswa = User::factory()->create(['username' => 'siswa_test_' . uniqid()]);
        $regularSiswa->assignRole('siswa');

        $this->actingAs($regularSiswa)
            ->get(route('portal.perizinan.dashboard'))
            ->assertStatus(403);
    }

    public function test_datatable_search_matches_student_name(): void
    {
        [$user, $target] = $this->seedPerizinanForNameSearch();

        $this->actingAs($user)
            ->getJson(route('portal.perizinan.keluar-masuk.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => 'Jindra'],
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.0.raw', $target->name);

        $this->actingAs($user)
            ->getJson(route('portal.perizinan.keluar-masuk.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'q' => 'Jindra',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.0.raw', $target->name);

        $this->actingAs($user)
            ->getJson(route('portal.perizinan.rekap-laporan.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => $target->nis],
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.1.raw', $target->name);
    }

    /**
     * @return array{0: User, 1: Siswa}
     */
    private function seedPerizinanForNameSearch(): array
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $target = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '880011',
            'name' => 'Jindra Rahimah',
            'gender' => 'L',
        ]);
        $other = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '880012',
            'name' => 'Budi Santri',
            'gender' => 'L',
        ]);

        foreach ([$target, $other] as $siswa) {
            Perizinan::create([
                'sekolah_id' => $sekolah->id,
                'siswa_id' => $siswa->id,
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
                'alasan' => 'Ke klinik',
                'tgl_mulai' => Carbon::now(),
                'tgl_sampai' => Carbon::now()->addHours(2),
                'status' => Perizinan::STATUS_DISETUJUI,
            ]);
        }

        $user = User::factory()->create(['username' => 'perizinan_search_' . uniqid()]);
        $user->assignRole('perizinan');

        return [$user, $target];
    }
}
