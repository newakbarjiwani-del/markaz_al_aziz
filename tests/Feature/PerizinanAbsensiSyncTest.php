<?php

namespace Tests\Feature;

use App\Models\JadwalAbsenHari;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsenSlot;
use App\Models\Pelajaran;
use App\Models\Perizinan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PerizinanAbsensiSyncService;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerizinanAbsensiSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_active_permit_in_slot_time_prefills_suggested_status_and_badge(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '112233',
            'name' => 'Budi Santri',
            'gender' => 'L',
            'status' => 1,
        ]);

        $guruUser = User::factory()->create(['username' => 'guru_sync_' . uniqid()]);
        $guru = \App\Models\Guru::create([
            'sekolah_id' => $sekolah->id,
            'user_id' => $guruUser->id,
            'nip' => '123456',
            'name' => 'Guru Pengampu',
        ]);
        $guruUser->update(['guru_id' => $guru->id]);
        $guruUser->assignRole('guru');

        $pelajaran = Pelajaran::create(['name' => 'Matematika']);
        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal Pagi MA',
            'assignment_type' => 'sekolah',
            'is_active' => true,
        ]);
        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwalAbsen->id,
            'day_of_week' => now()->dayOfWeekIso,
            'is_active' => true,
        ]);
        // Slot at 08:00 - 09:00
        $slot = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '08:00:00',
            'time_end' => '09:00:00',
            'tolerance_minutes' => 15,
        ]);

        $today = now()->toDateString();
        // Permit from 07:00 to 09:00 today
        $permit = Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Izin periksa ke klinik dokter',
            'tgl_mulai' => Carbon::parse($today . ' 07:00:00'),
            'tgl_sampai' => Carbon::parse($today . ' 09:00:00'),
            'status' => Perizinan::STATUS_DISETUJUI,
        ]);

        // Query students for attendance slot via Portal Guru API
        $response = $this->actingAs($guruUser)
            ->getJson(route('portal.guru.absensi-siswa.students', ['slot_id' => $slot->id]));

        $response->assertStatus(200);

        $studentData = collect($response->json('data.students'))->firstWhere('id', $siswa->id);
        $this->assertNotNull($studentData);
        $this->assertEquals(AttendanceStatus::SAKIT, $studentData['suggested_status']);
        $this->assertNotNull($studentData['perizinan']);
        $this->assertTrue($studentData['perizinan']['is_active']);
        $this->assertFalse($studentData['perizinan']['is_expired']);
    }

    public function test_expired_permit_before_slot_time_requires_normal_attendance(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '112244',
            'name' => 'Doni Santri',
            'gender' => 'L',
            'status' => 1,
        ]);

        $guruUser = User::factory()->create(['username' => 'guru_sync_2_' . uniqid()]);
        $guru = \App\Models\Guru::create([
            'sekolah_id' => $sekolah->id,
            'user_id' => $guruUser->id,
            'nip' => '123457',
            'name' => 'Guru Pengampu 2',
        ]);
        $guruUser->update(['guru_id' => $guru->id]);
        $guruUser->assignRole('guru');

        $pelajaran = Pelajaran::create(['name' => 'Bahasa Indonesia']);
        $jadwalAbsen = JadwalAbsen::create([
            'sekolah_id' => $sekolah->id,
            'name' => 'Jadwal Siang MA',
            'assignment_type' => 'sekolah',
            'is_active' => true,
        ]);
        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwalAbsen->id,
            'day_of_week' => now()->dayOfWeekIso,
            'is_active' => true,
        ]);
        // Slot at 09:30 - 10:30 (starts after permit planned return time of 09:00)
        $slot = JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $pelajaran->id,
            'guru_id' => $guru->id,
            'time_start' => '09:30:00',
            'time_end' => '10:30:00',
            'tolerance_minutes' => 15,
        ]);

        $today = now()->toDateString();
        // Permit ended at 09:00 today
        $permit = Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
            'alasan' => 'Beli perlengkapan',
            'tgl_mulai' => Carbon::parse($today . ' 07:00:00'),
            'tgl_sampai' => Carbon::parse($today . ' 09:00:00'),
            'status' => Perizinan::STATUS_DISETUJUI,
        ]);

        $response = $this->actingAs($guruUser)
            ->getJson(route('portal.guru.absensi-siswa.students', ['slot_id' => $slot->id]));

        $response->assertStatus(200);

        $studentData = collect($response->json('data.students'))->firstWhere('id', $siswa->id);
        $this->assertNotNull($studentData);
        // Permit expired before slot time, so suggested_status should be null
        $this->assertNull($studentData['suggested_status']);
        $this->assertNotNull($studentData['perizinan']);
        $this->assertFalse($studentData['perizinan']['is_active']);
        $this->assertTrue($studentData['perizinan']['is_expired']);
    }

    public function test_multiday_permit_is_active_for_all_days_in_range(): void
    {
        $sekolah = Sekolah::create(['code' => 'ma', 'name' => 'Madrasah Aliyah']);
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'nis' => '112255',
            'name' => 'Eka Santri',
            'gender' => 'P',
            'status' => 1,
        ]);

        $today = now()->toDateString();
        // Permit for 3 days starting yesterday until tomorrow
        $permit = Perizinan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $siswa->id,
            'jenis_perizinan' => Perizinan::JENIS_PULANG_LIBUR,
            'alasan' => 'Pulang libur keluarga',
            'tgl_mulai' => Carbon::now()->subDay()->startOfDay(),
            'tgl_sampai' => Carbon::now()->addDay()->endOfDay(),
            'status' => Perizinan::STATUS_DISETUJUI,
        ]);

        $activeMap = PerizinanAbsensiSyncService::activePerizinanMap([$siswa->id], $today);

        $this->assertTrue($activeMap->has($siswa->id));
        $this->assertTrue($activeMap->get($siswa->id)['is_active']);
        $this->assertEquals(AttendanceStatus::IZIN, $activeMap->get($siswa->id)['suggested_status']);
    }
}
