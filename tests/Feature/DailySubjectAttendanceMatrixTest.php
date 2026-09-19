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

class DailySubjectAttendanceMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_service_generates_daily_subject_matrix_data(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X MIPA 1', 'kelas' => 'X MIPA 1']);

        $siswa1 = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3001',
            'name' => 'Budi Santri',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $siswa2 = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3002',
            'name' => 'Siti Santri',
            'gender' => 'P',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $guru = Guru::create(['sekolah_id' => $sekolah->id, 'nip' => 'G300', 'name' => 'Ustadz Ahmad']);
        $pelajaran1 = Pelajaran::create(['sekolah_id' => $sekolah->id, 'name' => 'Matematika', 'code' => 'MTK']);
        $pelajaran2 = Pelajaran::create(['sekolah_id' => $sekolah->id, 'name' => 'Fisika', 'code' => 'FIS']);

        $jadwal = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal X MIPA 1',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwal->kelas()->attach($kelas->id);

        $date = Carbon::now()->startOfWeek()->toDateString(); // Monday
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
            'time_start' => '07:00:00',
            'time_end' => '08:00:00',
            'sort_order' => 1,
        ]);

        $slot2 = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran2->id,
            'guru_id' => $guru->id,
            'time_start' => '08:00:00',
            'time_end' => '09:00:00',
            'sort_order' => 2,
        ]);

        // Siswa 1: Hadir slot 1, Alpha slot 2
        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa1->id,
            'jadwal_absen_slot_id' => $slot1->id,
            'date' => $date,
            'status' => AttendanceStatus::HADIR,
            'time_in' => '07:10:00',
        ]);

        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa1->id,
            'jadwal_absen_slot_id' => $slot2->id,
            'date' => $date,
            'status' => AttendanceStatus::ALPHA,
        ]);

        $service = app(DailySubjectAttendanceMatrixService::class);
        $result = $service->getDailyMatrixData($date, $kelas->id);

        $this->assertEquals($date, $result['date']);
        $this->assertCount(2, $result['subject_headers']);
        $this->assertCount(2, $result['students']);

        $student1Data = $result['students'][0];
        $this->assertEquals('Budi Santri', $student1Data['name']);
        $this->assertEquals('hadir', $student1Data['subjects'][0]['status']);
        $this->assertEquals('alpha', $student1Data['subjects'][1]['status']);
    }

    public function test_guru_can_access_daily_subject_matrix_endpoint(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X MIPA 1', 'kelas' => 'X MIPA 1']);
        $guru = Guru::create(['sekolah_id' => $sekolah->id, 'nip' => 'G301', 'name' => 'Ustadz Budi']);

        $guruUser = User::factory()->create([
            'username' => 'guru_daily_' . uniqid(),
            'guru_id' => $guru->id,
            'sekolah_id' => $sekolah->id,
        ]);
        $guruUser->assignRole('guru');

        $jadwal = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal Test',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwal->kelas()->attach($kelas->id);

        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwal->id,
            'day_of_week' => 1,
            'is_active' => true,
        ]);

        $pelajaran = Pelajaran::create(['sekolah_id' => $sekolah->id, 'name' => 'Biologi', 'code' => 'BIO']);

        JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '07:00:00',
            'time_end' => '08:00:00',
        ]);

        $response = $this->actingAs($guruUser)
            ->get(route('portal.guru.rekap-siswa.daily-subject-matrix', [
                'date' => Carbon::now()->toDateString(),
                'kelas_id' => $kelas->id,
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure(['date', 'subject_headers', 'students']);
    }

    public function test_admin_can_access_daily_subject_matrix_endpoint(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X MIPA 2', 'kelas' => 'X MIPA 2']);
        $adminUser = User::factory()->create([
            'username' => 'admin_daily_' . uniqid(),
            'sekolah_id' => $sekolah->id,
        ]);
        $adminUser->assignRole('admin');

        $response = $this->actingAs($adminUser)
            ->get(route('admin.absensi.rekap-presensi.daily-subject-matrix', [
                'date' => '2026-07-31',
                'kelas_id' => $kelas->id,
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure(['date', 'subject_headers', 'students']);
    }

    public function test_service_detects_registered_holidays_and_suppresses_alpha(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X MIPA 1', 'kelas' => 'X MIPA 1']);
        Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3009',
            'name' => 'Bambang Libur',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        \App\Models\HariLibur::create([
            'sekolah_id' => $sekolah->id,
            'date' => '2026-08-01',
            'name' => 'Libur Nasional Test',
            'applies_to' => \App\Models\HariLibur::APPLIES_BOTH,
        ]);

        $service = app(DailySubjectAttendanceMatrixService::class);
        $result = $service->getDailyMatrixData('2026-08-01', $kelas->id);

        $this->assertTrue($result['is_holiday']);
        $this->assertEquals('Libur Nasional Test', $result['holiday_name']);
        if (! empty($result['students'])) {
            $this->assertEquals(0, $result['students'][0]['summary']['alpha']);
        }
    }

    public function test_exempt_session_suppresses_stored_alpha_records(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X IPA 2', 'kelas' => 'X IPA 2']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3010',
            'name' => 'Santri Alpha',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $guru = Guru::create(['sekolah_id' => $sekolah->id, 'nip' => 'G310', 'name' => 'Ustadz Test']);
        $pelajaran = Pelajaran::create(['sekolah_id' => $sekolah->id, 'name' => 'Matematika', 'code' => 'MTK2']);
        $jadwal = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal Malam',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwal->kelas()->attach($kelas->id);

        $date = Carbon::parse('2026-08-16')->toDateString();
        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwal->id,
            'day_of_week' => Carbon::parse($date)->dayOfWeekIso,
            'is_active' => true,
        ]);
        $slot = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '19:00:00',
            'time_end' => '20:00:00',
            'sort_order' => 1,
        ]);

        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'date' => $date,
            'status' => AttendanceStatus::ALPHA,
        ]);

        AbsensiSesiPengecualian::create([
            'jadwal_absen_slot_id' => $slot->id,
            'date' => $date,
            'reason' => 'Diganti yasin bersama',
        ]);

        $result = app(DailySubjectAttendanceMatrixService::class)->getDailyMatrixData($date, $kelas->id);
        $student = $result['students'][0];

        $this->assertNull($student['subjects'][0]['status']);
        $this->assertTrue($student['subjects'][0]['is_exempt']);
        $this->assertEquals(0, $student['summary']['alpha']);
    }

    public function test_weekend_matrix_loads_for_class_without_schedule(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'XII B', 'kelas' => 'XII B']);
        Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3011',
            'name' => 'Santri Weekend',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $adminUser = User::factory()->create([
            'username' => 'admin_weekend_'.uniqid(),
            'sekolah_id' => $sekolah->id,
        ]);
        $adminUser->assignRole('admin');

        $response = $this->actingAs($adminUser)
            ->getJson(route('admin.absensi.rekap-presensi.daily-subject-matrix', [
                'date' => '2026-08-16',
                'kelas_id' => $kelas->id,
            ]));

        $response->assertOk()
            ->assertJsonPath('is_weekend', true)
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.summary.alpha', 0);
    }
}
