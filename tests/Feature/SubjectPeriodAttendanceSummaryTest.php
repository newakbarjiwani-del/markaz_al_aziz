<?php

namespace Tests\Feature;

use App\Models\AbsensiSiswa;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsenHari;
use App\Models\JadwalAbsenSlot;
use App\Models\Kelas;
use App\Models\Pelajaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Attendance\SubjectPeriodAttendanceSummaryService;
use App\Support\AttendanceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectPeriodAttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_returns_accurate_period_summary_per_subject(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X IPA 1', 'kelas' => 'X IPA 1']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3000001',
            'name' => 'Salman Handayani',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $pelajaran = Pelajaran::create(['name' => 'Bahasa Indonesia']);

        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal MA',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwalAbsen->kelas()->attach($kelas->id);

        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwalAbsen->id,
            'day_of_week' => 3, // Wednesday
            'is_active' => true,
        ]);

        $guru = \App\Models\Guru::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Guru Test',
            'nip' => '1234567890',
            'gender' => 'L',
        ]);

        $slot = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '07:00',
            'time_end' => '08:00',
            'sort_order' => 1,
        ]);

        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'date' => '2026-07-01',
            'status' => AttendanceStatus::HADIR,
            'time_in' => '07:05:00',
        ]);

        $service = app(SubjectPeriodAttendanceSummaryService::class);
        $result = $service->getSummaryData('2026-07-01', '2026-07-31', $kelas->id, $pelajaran->id, null, $sekolah->id);

        $this->assertEquals($pelajaran->name, $result['pelajaran_name']);
        $this->assertNotEmpty($result['students']);
        $this->assertEquals(1, $result['students'][0]['hadir']);
        $this->assertEquals(1, $result['students'][0]['total_sessions']);
        $this->assertEquals(100.0, $result['students'][0]['percentage']);
    }

    public function test_admin_can_access_subject_period_summary_endpoint(): void
    {
        $this->seed(\Database\Seeders\SyncServerRolesSeeder::class);

        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X IPA 1', 'kelas' => 'X IPA 1']);
        $adminUser = User::factory()->create([
            'username' => 'admin_period_' . uniqid(),
            'sekolah_id' => $sekolah->id,
        ]);
        $adminUser->assignRole('admin');

        $response = $this->actingAs($adminUser)
            ->get(route('admin.absensi.rekap-presensi.subject-period-summary', [
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
                'kelas_id' => $kelas->id,
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure(['start_date', 'end_date', 'period_label', 'students']);
    }

    public function test_sunday_schedule_is_counted_when_active_slot_exists(): void
    {
        $sekolah = Sekolah::create(['code' => 'takhasus', 'name' => 'Takhasus']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'Tahfidz A', 'kelas' => 'Tahfidz A']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '4000001',
            'name' => 'Ahmad Santri',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $pelajaran = Pelajaran::create(['name' => 'Pengajian Minggu']);

        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal Pesantren Minggu',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwalAbsen->kelas()->attach($kelas->id);

        // Day 7 = Sunday
        $hariMinggu = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwalAbsen->id,
            'day_of_week' => 7,
            'is_active' => true,
        ]);

        $guru = \App\Models\Guru::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Kiai Test',
            'nip' => '1234567891',
            'gender' => 'L',
        ]);

        $slot = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hariMinggu->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '08:00',
            'time_end' => '09:00',
            'sort_order' => 1,
        ]);

        // Record attendance on Sunday 2026-07-05
        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'date' => '2026-07-05', // Sunday
            'status' => AttendanceStatus::HADIR,
            'time_in' => '08:05:00',
        ]);

        $service = app(SubjectPeriodAttendanceSummaryService::class);
        $result = $service->getSummaryData('2026-07-01', '2026-07-31', $kelas->id, $pelajaran->id, null, $sekolah->id);

        // Only the Sunday where attendance was actually taken counts as a conducted session.
        $this->assertEquals(1, $result['total_sessions_count']);
        $this->assertEquals(1, $result['students'][0]['hadir']);
        $this->assertEquals(1, $result['students'][0]['total_sessions']);
        $this->assertEquals(100.0, $result['students'][0]['percentage']);
    }

    public function test_period_summary_includes_attendance_slots_not_in_class_schedule(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelasA = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X IPA 1', 'kelas' => 'X IPA 1']);
        $kelasB = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X IPA 2', 'kelas' => 'X IPA 2']);

        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelasB->id,
            'nis' => '3000012',
            'name' => 'Adhiarja Maryadi',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $pelajaran = Pelajaran::create(['name' => 'Matematika']);
        $guru = \App\Models\Guru::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Kaka Jailani',
            'nip' => '1234567892',
            'gender' => 'L',
        ]);

        // Jadwal hanya untuk kelas A, bukan kelas B
        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal X IPA 1',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwalAbsen->kelas()->attach($kelasA->id);

        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwalAbsen->id,
            'day_of_week' => 7,
            'is_active' => true,
        ]);

        $slot = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '19:38',
            'time_end' => '22:38',
            'sort_order' => 1,
        ]);

        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'date' => '2026-08-16',
            'status' => AttendanceStatus::HADIR,
            'time_in' => '19:40:00',
        ]);

        $service = app(SubjectPeriodAttendanceSummaryService::class);
        $result = $service->getSummaryData('2026-08-01', '2026-08-16', $kelasB->id, null, null, $sekolah->id);

        $student = collect($result['students'])->firstWhere('name', 'Adhiarja Maryadi');
        $this->assertNotNull($student);
        $this->assertEquals(1, $result['total_sessions_count']);
        $this->assertEquals(1, $student['total_sessions']);
        $this->assertEquals(1, $student['hadir']);
        $this->assertEquals(100.0, $student['percentage']);
    }

    public function test_period_summary_excludes_exempt_conducted_session(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X IPA 2', 'kelas' => 'X IPA 2']);

        $siswaHadir = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3000012',
            'name' => 'Adhiarja Maryadi',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $siswaAlpha = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3000013',
            'name' => 'Amelia Fujiati',
            'gender' => 'P',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $pelajaran = Pelajaran::create(['name' => 'Matematika']);
        $guru = \App\Models\Guru::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Kala Jailani',
            'nip' => '1234567893',
            'gender' => 'L',
        ]);

        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal X IPA 2',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwalAbsen->kelas()->attach($kelas->id);

        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwalAbsen->id,
            'day_of_week' => 7,
            'is_active' => true,
        ]);

        $slot = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '19:38',
            'time_end' => '22:38',
            'sort_order' => 1,
        ]);

        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswaHadir->id,
            'jadwal_absen_slot_id' => $slot->id,
            'date' => '2026-08-16',
            'status' => AttendanceStatus::HADIR,
            'time_in' => '19:40:00',
        ]);

        \App\Models\AbsensiSesiPengecualian::create([
            'jadwal_absen_slot_id' => $slot->id,
            'date' => '2026-08-16',
            'reason' => 'Diganti kegiatan yasin',
        ]);

        $service = app(SubjectPeriodAttendanceSummaryService::class);
        $result = $service->getSummaryData('2026-08-01', '2026-08-16', $kelas->id, null, null, $sekolah->id);

        $this->assertEquals(0, $result['total_sessions_count']);

        foreach ([$siswaHadir->name, $siswaAlpha->name] as $name) {
            $student = collect($result['students'])->firstWhere('name', $name);
            $this->assertNotNull($student, "Missing student: {$name}");
            $this->assertEquals(0, $student['total_sessions'], "Unexpected sessions for {$name}");
            $this->assertEquals(0, $student['alpha'], "Unexpected alpha for {$name}");
            $this->assertEquals(0, $student['hadir'], "Unexpected hadir for {$name}");
        }
    }

    public function test_period_summary_accumulates_all_conducted_days_like_daily_matrix(): void
    {
        $sekolah = Sekolah::create(['code' => 'mts', 'name' => 'MTs']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'VIII A', 'kelas' => 'VIII A']);

        $siswaHadir = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '512336041084250001',
            'name' => 'Abdullah Jabbarudin Hafid',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $siswaMixed = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '512336041084250006',
            'name' => 'Ahmad Danial Ibnu Hidayat',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $pelajaranA = Pelajaran::create(['name' => 'Sorogan']);
        $pelajaranB = Pelajaran::create(['name' => 'Pengajian']);
        $guru = \App\Models\Guru::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Guru Rekap',
            'nip' => '9988776655',
            'gender' => 'L',
        ]);

        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal VIII A',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);
        $jadwalAbsen->kelas()->attach($kelas->id);

        // Saturday Aug 1, Sunday Aug 2, Monday Aug 3 2026
        $dates = [
            '2026-08-01' => 6,
            '2026-08-02' => 7,
            '2026-08-03' => 1,
        ];

        $slotsByDate = [];
        foreach ($dates as $date => $dow) {
            $hari = JadwalAbsenHari::create([
                'jadwal_absen_id' => $jadwalAbsen->id,
                'day_of_week' => $dow,
                'is_active' => true,
            ]);

            $slot1 = JadwalAbsenSlot::create([
                'jadwal_absen_hari_id' => $hari->id,
                'pelajaran_id' => $pelajaranA->id,
                'guru_id' => $guru->id,
                'time_start' => '05:20',
                'time_end' => '06:30',
                'sort_order' => 1,
            ]);
            $slot2 = JadwalAbsenSlot::create([
                'jadwal_absen_hari_id' => $hari->id,
                'pelajaran_id' => $pelajaranA->id,
                'guru_id' => $guru->id,
                'time_start' => '12:30',
                'time_end' => '13:30',
                'sort_order' => 2,
            ]);
            $slot3 = JadwalAbsenSlot::create([
                'jadwal_absen_hari_id' => $hari->id,
                'pelajaran_id' => $pelajaranB->id,
                'guru_id' => $guru->id,
                'time_start' => '18:40',
                'time_end' => '19:30',
                'sort_order' => 3,
            ]);

            $slotsByDate[$date] = [$slot1, $slot2, $slot3];
        }

        foreach ($slotsByDate as $date => $slots) {
            foreach ($slots as $index => $slot) {
                AbsensiSiswa::create([
                    'sekolah_id' => $sekolah->id,
                    'siswa_id' => $siswaHadir->id,
                    'jadwal_absen_slot_id' => $slot->id,
                    'date' => $date,
                    'status' => AttendanceStatus::HADIR,
                    'time_in' => '05:25:00',
                ]);

                AbsensiSiswa::create([
                    'sekolah_id' => $sekolah->id,
                    'siswa_id' => $siswaMixed->id,
                    'jadwal_absen_slot_id' => $slot->id,
                    'date' => $date,
                    // Third slot on first day becomes alpha for mixed student.
                    'status' => ($date === '2026-08-01' && $index === 2)
                        ? AttendanceStatus::ALPHA
                        : AttendanceStatus::HADIR,
                    'time_in' => '05:25:00',
                ]);
            }
        }

        $service = app(SubjectPeriodAttendanceSummaryService::class);
        $result = $service->getSummaryData('2026-08-01', '2026-08-31', $kelas->id, null, null, $sekolah->id);

        // 3 days × 3 slots = 9 conducted sessions
        $this->assertEquals(9, $result['total_sessions_count']);
        $this->assertEquals(3, $result['active_school_days']);

        $abdullah = collect($result['students'])->firstWhere('name', 'Abdullah Jabbarudin Hafid');
        $this->assertNotNull($abdullah);
        $this->assertEquals(9, $abdullah['total_sessions']);
        $this->assertEquals(9, $abdullah['hadir']);
        $this->assertEquals(0, $abdullah['alpha']);
        $this->assertEquals(100.0, $abdullah['percentage']);

        $danial = collect($result['students'])->firstWhere('name', 'Ahmad Danial Ibnu Hidayat');
        $this->assertNotNull($danial);
        $this->assertEquals(9, $danial['total_sessions']);
        $this->assertEquals(8, $danial['hadir']);
        $this->assertEquals(1, $danial['alpha']);
        $this->assertEquals(88.9, $danial['percentage']);
    }

    public function test_period_summary_does_not_infer_alpha_for_unconducted_scheduled_days(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $kelas = Kelas::create(['sekolah_id' => $sekolah->id, 'name' => 'X IPA 2', 'kelas' => 'X IPA 2']);

        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '3000014',
            'name' => 'Bella Rahayu',
            'gender' => 'P',
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        $pelajaran = Pelajaran::create(['name' => 'Matematika']);
        $guru = \App\Models\Guru::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Kala Jailani',
            'nip' => '1234567894',
            'gender' => 'L',
        ]);

        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal Harian',
            'assignment_type' => 'sekolah',
            'is_active' => true,
        ]);

        for ($dow = 1; $dow <= 7; $dow++) {
            $hari = JadwalAbsenHari::create([
                'jadwal_absen_id' => $jadwalAbsen->id,
                'day_of_week' => $dow,
                'is_active' => true,
            ]);

            JadwalAbsenSlot::create([
                'jadwal_absen_hari_id' => $hari->id,
                'pelajaran_id' => $pelajaran->id,
                'guru_id' => $guru->id,
                'time_start' => '07:00',
                'time_end' => '08:00',
                'sort_order' => 1,
            ]);
        }

        $slot = JadwalAbsenSlot::query()->first();

        AbsensiSiswa::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'date' => '2026-08-16',
            'status' => AttendanceStatus::HADIR,
            'time_in' => '07:05:00',
        ]);

        $service = app(SubjectPeriodAttendanceSummaryService::class);
        $result = $service->getSummaryData('2026-08-01', '2026-08-16', $kelas->id, null, null, $sekolah->id);

        $student = collect($result['students'])->firstWhere('name', 'Bella Rahayu');
        $this->assertNotNull($student);
        $this->assertEquals(1, $result['total_sessions_count']);
        $this->assertEquals(1, $student['total_sessions']);
        $this->assertEquals(1, $student['hadir']);
        $this->assertEquals(0, $student['alpha']);
        $this->assertEquals(100.0, $student['percentage']);
    }
}

