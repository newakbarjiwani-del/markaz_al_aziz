<?php

namespace Tests\Feature;

use App\Models\AbsensiSiswa;
use App\Models\AbsensiSesiPengecualian;
use App\Models\Guru;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsenHari;
use App\Models\JadwalAbsenSlot;
use App\Models\Kelas;
use App\Models\Pelajaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Attendance\DailySubjectAttendanceMatrixService;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsensiSesiPengecualianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** @return array{sekolah: Sekolah, kelas: Kelas, guru: Guru, slot1: JadwalAbsenSlot, slot2: JadwalAbsenSlot, siswa1: Siswa, siswa2: Siswa, date: string} */
    private function seedScheduleFixture(): array
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X MIPA 1', 'kelas' => 'X MIPA 1']);
        $guru = Guru::create(['sekolah_id' => $sekolah->id, 'nip' => 'G400', 'name' => 'Ustadz Malik']);
        $pelajaran1 = Pelajaran::create(['sekolah_id' => $sekolah->id, 'name' => 'Pengajian Qur\'an', 'code' => 'QUR']);
        $pelajaran2 = Pelajaran::create(['sekolah_id' => $sekolah->id, 'name' => 'Bahasa Arab', 'code' => 'ARB']);

        $jadwal = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal Malam',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwal->kelas()->attach($kelas->id);

        $date = Carbon::now()->startOfWeek()->toDateString();
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;

        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwal->id,
            'day_of_week' => $dayOfWeek,
            'is_active' => true,
        ]);

        $slot1 = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran1->id,
            'guru_id' => $guru->id,
            'time_start' => '19:00:00',
            'time_end' => '20:00:00',
            'sort_order' => 1,
        ]);

        $slot2 = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran2->id,
            'guru_id' => $guru->id,
            'time_start' => '20:00:00',
            'time_end' => '21:00:00',
            'sort_order' => 2,
        ]);

        $siswa1 = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '4001',
            'name' => 'Ahmad Santri',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $siswa2 = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '4002',
            'name' => 'Fatimah Santri',
            'gender' => 'P',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        return compact('sekolah', 'kelas', 'guru', 'slot1', 'slot2', 'siswa1', 'siswa2', 'date');
    }

    public function test_guru_can_mark_session_exemption_for_today(): void
    {
        $fixture = $this->seedScheduleFixture();
        $guruUser = User::factory()->create([
            'username' => 'guru_exempt_'.uniqid(),
            'guru_id' => $fixture['guru']->id,
            'sekolah_id' => $fixture['sekolah']->id,
        ]);
        $guruUser->assignRole('guru');

        $response = $this->actingAs($guruUser)->postJson(route('portal.guru.absensi-siswa.sesi-pengecualian.store'), [
            'jadwal_absen_slot_id' => $fixture['slot1']->id,
            'reason' => 'Diganti yasin bersama, tidak diabsen',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_exemption.active', true);

        $this->assertDatabaseHas('absensi_sesi_pengecualian', [
            'jadwal_absen_slot_id' => $fixture['slot1']->id,
            'reason' => 'Diganti yasin bersama, tidak diabsen',
            'created_by_user_id' => $guruUser->id,
        ]);
    }

    public function test_exempt_session_suppresses_alpha_in_daily_matrix(): void
    {
        $fixture = $this->seedScheduleFixture();

        AbsensiSiswa::create([
            'sekolah_id' => $fixture['sekolah']->id,
            'siswa_id' => $fixture['siswa1']->id,
            'jadwal_absen_slot_id' => $fixture['slot2']->id,
            'date' => $fixture['date'],
            'status' => AttendanceStatus::HADIR,
            'time_in' => '20:05:00',
        ]);

        AbsensiSesiPengecualian::create([
            'jadwal_absen_slot_id' => $fixture['slot1']->id,
            'date' => $fixture['date'],
            'reason' => 'Diganti yasin bersama',
        ]);

        $result = app(DailySubjectAttendanceMatrixService::class)
            ->getDailyMatrixData($fixture['date'], $fixture['kelas']->id);

        $student = collect($result['students'])->firstWhere('name', 'Ahmad Santri');
        $this->assertNotNull($student);
        $this->assertTrue($result['subject_headers'][0]['is_exempt']);
        $this->assertNull($student['subjects'][0]['status']);
        $this->assertTrue($student['subjects'][0]['is_exempt']);
        $this->assertEquals(0, $student['summary']['alpha']);
    }

    public function test_guru_can_revoke_session_exemption(): void
    {
        $fixture = $this->seedScheduleFixture();
        $guruUser = User::factory()->create([
            'username' => 'guru_revoke_'.uniqid(),
            'guru_id' => $fixture['guru']->id,
            'sekolah_id' => $fixture['sekolah']->id,
        ]);
        $guruUser->assignRole('guru');

        $row = AbsensiSesiPengecualian::create([
            'jadwal_absen_slot_id' => $fixture['slot1']->id,
            'date' => now()->toDateString(),
            'reason' => 'Uji coba',
        ]);

        $response = $this->actingAs($guruUser)->deleteJson(route('portal.guru.absensi-siswa.sesi-pengecualian.destroy'), [
            'jadwal_absen_slot_id' => $fixture['slot1']->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('absensi_sesi_pengecualian', ['id' => $row->id]);
    }

    public function test_admin_can_view_and_delete_session_exemption(): void
    {
        $fixture = $this->seedScheduleFixture();
        $admin = User::factory()->create([
            'username' => 'admin_exempt_'.uniqid(),
            'sekolah_id' => $fixture['sekolah']->id,
        ]);
        $admin->assignRole('admin');

        $row = AbsensiSesiPengecualian::create([
            'jadwal_absen_slot_id' => $fixture['slot1']->id,
            'date' => $fixture['date'],
            'reason' => 'Kegiatan pondok',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.absensi.sesi-pengecualian.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.absensi.sesi-pengecualian.data', ['draw' => 1]))
            ->assertOk();

        $this->actingAs($admin)
            ->delete(route('admin.absensi.sesi-pengecualian.destroy', $row))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('absensi_sesi_pengecualian', ['id' => $row->id]);
    }

    public function test_guru_cannot_mark_exemption_for_other_guru_slot(): void
    {
        $fixture = $this->seedScheduleFixture();
        $otherGuru = Guru::create(['sekolah_id' => $fixture['sekolah']->id, 'nip' => 'G401', 'name' => 'Ustadz Lain']);
        $guruUser = User::factory()->create([
            'username' => 'guru_other_'.uniqid(),
            'guru_id' => $otherGuru->id,
            'sekolah_id' => $fixture['sekolah']->id,
        ]);
        $guruUser->assignRole('guru');

        $this->actingAs($guruUser)->postJson(route('portal.guru.absensi-siswa.sesi-pengecualian.store'), [
            'jadwal_absen_slot_id' => $fixture['slot1']->id,
            'reason' => 'Tidak sah',
        ])->assertNotFound();
    }
}
