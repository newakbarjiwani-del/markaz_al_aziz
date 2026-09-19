<?php

namespace Tests\Feature;

use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsenHari;
use App\Models\JadwalAbsenSlot;
use App\Models\Kelas;
use App\Models\Pelajaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Weekday;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidCheckinTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;

    protected Kelas $kelas;

    protected Siswa $siswa;

    protected Guru $guru;

    protected User $admin;

    protected Pelajaran $pelajaran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->sekolah = Sekolah::create([
            'code' => 'ma',
            'name' => 'Madrasah Aliyah',
            'unit' => 'MA',
        ]);

        $this->kelas = Kelas::create([
            'sekolah_id' => $this->sekolah->id,
            'name' => 'Class X-A',
            'is_active' => true,
        ]);

        $this->siswa = Siswa::create([
            'sekolah_id' => $this->sekolah->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '1234567890',
            'name' => 'Test Student',
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
        ]);
        assignRfid($this->siswa, 'RFID_TEST_001');

        $this->guru = Guru::create([
            'sekolah_id' => $this->sekolah->id,
            'nip' => '198203112009031002',
            'name' => 'Guru Test',
            'status' => 'aktif',
        ]);

        $this->pelajaran = Pelajaran::create([
            'sekolah_id' => $this->sekolah->id,
            'name' => 'Matematika',
            'code' => 'MTK',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['username' => 'admin_test_'.uniqid()]);
        $this->admin->assignRole('admin');
    }

    private function createScheduleSlot(string $timeStart, string $timeEnd, int $tolerance = 15): JadwalAbsenSlot
    {
        $dayOfWeek = Weekday::fromDate(now());

        $jadwal = JadwalAbsen::create([
            'sekolah_id' => $this->sekolah->id,
            'name' => 'Jadwal RFID Test',
            'assignment_type' => 'kelas',
            'is_active' => true,
        ]);

        $jadwal->kelas()->sync([$this->kelas->id]);

        $hari = JadwalAbsenHari::create([
            'jadwal_absen_id' => $jadwal->id,
            'day_of_week' => $dayOfWeek,
            'is_active' => true,
        ]);

        return JadwalAbsenSlot::create([
            'jadwal_absen_hari_id' => $hari->id,
            'pelajaran_id' => $this->pelajaran->id,
            'guru_id' => $this->guru->id,
            'time_start' => $timeStart,
            'time_end' => $timeEnd,
            'tolerance_minutes' => $tolerance,
            'sort_order' => 0,
        ]);
    }

    public function test_admin_can_access_rfid_checkin_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.absensi.absensi-rfid'));

        $response->assertStatus(200)
            ->assertSee('Kiosk Presensi RFID');
    }

    public function test_rfid_checkin_success_on_time(): void
    {
        $timeStart = now()->subMinutes(5)->format('H:i');
        $timeEnd = now()->addMinutes(45)->format('H:i');
        $slot = $this->createScheduleSlot($timeStart, $timeEnd, 15);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.absensi.absensi-rfid.store'), [
                'rfid_uid' => 'RFID_TEST_001',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_recorded', false)
            ->assertJsonPath('data.attendance.status', 'Hadir');

        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $this->siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'status' => 'hadir',
            'method' => 'rfid',
        ]);
    }

    public function test_rfid_checkin_success_late(): void
    {
        // 20 minutes ago start time, with 5 minutes tolerance (meaning they are late by 15 mins)
        $timeStart = now()->subMinutes(20)->format('H:i');
        $timeEnd = now()->addMinutes(45)->format('H:i');
        $slot = $this->createScheduleSlot($timeStart, $timeEnd, 5);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.absensi.absensi-rfid.store'), [
                'rfid_uid' => 'RFID_TEST_001',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_recorded', false)
            ->assertJsonPath('data.attendance.status', 'Terlambat');

        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $this->siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'status' => 'terlambat',
            'method' => 'rfid',
        ]);
    }

    public function test_rfid_checkin_already_checked_in(): void
    {
        $timeStart = now()->subMinutes(5)->format('H:i');
        $timeEnd = now()->addMinutes(45)->format('H:i');
        $slot = $this->createScheduleSlot($timeStart, $timeEnd, 15);

        // Pre-create attendance
        AbsensiSiswa::create([
            'sekolah_id' => $this->sekolah->id,
            'siswa_id' => $this->siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'date' => now()->toDateString(),
            'status' => 'hadir',
            'method' => 'rfid',
            'time_in' => now()->format('H:i'),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.absensi.absensi-rfid.store'), [
                'rfid_uid' => 'RFID_TEST_001',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_recorded', true)
            ->assertJsonPath('message', 'Anda sudah melakukan absensi hari ini.');
    }

    public function test_rfid_checkin_no_active_slot_upcoming(): void
    {
        // starts in 2 hours
        $timeStart = now()->addHours(2)->format('H:i');
        $timeEnd = now()->addHours(3)->format('H:i');
        $this->createScheduleSlot($timeStart, $timeEnd, 15);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.absensi.absensi-rfid.store'), [
                'rfid_uid' => 'RFID_TEST_001',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertSee('Jadwal terdekat');
    }

    public function test_rfid_checkin_no_active_slot_expired(): void
    {
        // ended 2 hours ago
        $timeStart = now()->subHours(3)->format('H:i');
        $timeEnd = now()->subHours(2)->format('H:i');
        $this->createScheduleSlot($timeStart, $timeEnd, 15);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.absensi.absensi-rfid.store'), [
                'rfid_uid' => 'RFID_TEST_001',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertSee('Seluruh jadwal absensi hari ini telah berakhir.');
    }

    public function test_rfid_checkin_blocked_card(): void
    {
        assignRfid($this->siswa, 'RFID_TEST_001', blocked: true);

        $timeStart = now()->subMinutes(5)->format('H:i');
        $timeEnd = now()->addMinutes(45)->format('H:i');
        $this->createScheduleSlot($timeStart, $timeEnd, 15);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.absensi.absensi-rfid.store'), [
                'rfid_uid' => 'RFID_TEST_001',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertSee('diblokir');
    }

    public function test_rfid_checkin_inactive_student(): void
    {
        $this->siswa->update(['status' => Siswa::STATUS_INACTIVE]);

        $timeStart = now()->subMinutes(5)->format('H:i');
        $timeEnd = now()->addMinutes(45)->format('H:i');
        $this->createScheduleSlot($timeStart, $timeEnd, 15);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.absensi.absensi-rfid.store'), [
                'rfid_uid' => 'RFID_TEST_001',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertSee('tidak aktif');
    }

    public function test_rfid_active_slots_endpoint_works(): void
    {
        $timeStart = now()->subMinutes(10)->format('H:i');
        $timeEnd = now()->addMinutes(30)->format('H:i');
        $this->createScheduleSlot($timeStart, $timeEnd, 5);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.absensi.absensi-rfid.active-slots'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'pelajaran',
                        'time_start',
                        'time_end',
                        'late_start',
                        'sekolah_name',
                        'sekolah_code',
                        'status',
                        'assignment_type',
                    ],
                ],
            ]);
    }

    public function test_rfid_recent_lists_all_today_taps_not_limited_subset(): void
    {
        $timeStart = now()->subMinutes(10)->format('H:i');
        $timeEnd = now()->addMinutes(30)->format('H:i');
        $slot = $this->createScheduleSlot($timeStart, $timeEnd, 15);

        for ($i = 0; $i < 8; $i++) {
            AbsensiSiswa::create([
                'sekolah_id' => $this->sekolah->id,
                'siswa_id' => $this->siswa->id,
                'jadwal_absen_slot_id' => $slot->id,
                'date' => now()->toDateString(),
                'status' => 'hadir',
                'method' => 'rfid',
                'time_in' => now()->format('H:i'),
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.absensi.absensi-rfid.recent'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 8)
            ->assertJsonCount(8, 'data');
    }
}
