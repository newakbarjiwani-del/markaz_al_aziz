<?php

namespace Tests\Feature;

use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\JadwalAbsen;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Attendance\MonthlyAttendanceMatrixService;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyAttendanceMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_service_calculates_daily_attendance_matrix_correctly(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X-A', 'kelas' => 'X-A']);

        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '1001',
            'name' => 'Ahmad Santri',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $currentMonth = Carbon::now()->format('Y-m');

        // Create absensi records for day 1 (Hadir) and day 2 (Alpha)
        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'date' => Carbon::now()->startOfMonth()->toDateString(),
            'status' => AttendanceStatus::HADIR,
            'time_in' => '07:15:00',
        ]);

        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'date' => Carbon::now()->startOfMonth()->addDay()->toDateString(),
            'status' => AttendanceStatus::ALPHA,
        ]);

        $service = app(MonthlyAttendanceMatrixService::class);
        $result = $service->getMatrixData($currentMonth, $kelas->id);

        $this->assertEquals($currentMonth, $result['month']);
        $this->assertCount(1, $result['students']);

        $studentData = $result['students'][0];
        $this->assertEquals('Ahmad Santri', $studentData['name']);
        $this->assertEquals(AttendanceStatus::HADIR, $studentData['days'][1]['status']);
        $this->assertEquals(AttendanceStatus::ALPHA, $studentData['days'][2]['status']);
        $this->assertEquals(1, $studentData['summary']['hadir']);
        $this->assertEquals(1, $studentData['summary']['alpha']);
    }

    public function test_guru_can_access_monthly_matrix_endpoint(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'XI-B', 'kelas' => 'XI-B']);

        $guru = Guru::create([
            'sekolah_id' => $sekolah->id,
            'nip' => 'G999',
            'name' => 'Ustadz Matriks',
        ]);

        $guruUser = User::factory()->create([
            'username' => 'guru_matrix_' . uniqid(),
            'guru_id' => $guru->id,
            'sekolah_id' => $sekolah->id,
        ]);
        $guruUser->assignRole('guru');

        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal Kelas XI-B',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwalAbsen->kelas()->attach($kelas->id);

        $hari = \App\Models\JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwalAbsen->id,
            'day_of_week' => 1,
            'is_active' => true,
        ]);

        $pelajaran = \App\Models\Pelajaran::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Matematika',
            'code' => 'MTK',
        ]);

        \App\Models\JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '07:00:00',
            'time_end' => '12:00:00',
        ]);

        $response = $this->actingAs($guruUser)
            ->get(route('portal.guru.rekap-siswa.matrix', ['kelas_id' => $kelas->id]));

        $response->assertStatus(200)
            ->assertJsonStructure(['month', 'days_in_month', 'days_header', 'students']);
    }

    public function test_admin_can_access_rekap_presensi_matrix_endpoint(): void
    {
        $admin = User::factory()->create(['username' => 'admin_matrix_' . uniqid()]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.absensi.rekap-presensi.matrix'));

        $response->assertStatus(200)
            ->assertJsonStructure(['month', 'days_in_month', 'days_header', 'students']);
    }
}
