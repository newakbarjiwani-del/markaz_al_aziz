<?php

use App\Models\AbsensiSiswa;
use App\Models\Tagihan;
use App\Support\AttendanceDashboard;
use App\Support\TagihanDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->fixture = FinanceFixtures::schoolWithStudent();
});

test('attendance dashboard counts distinct students not multi-slot rows', function () {
    $siswa = $this->fixture->siswa;
    $today = today();

    AbsensiSiswa::create([
        'sekolah_id' => $siswa->sekolah_id,
        'siswa_id' => $siswa->id,
        'date' => $today,
        'status' => 'hadir',
        'method' => 'manual',
        'time_in' => '07:00:00',
    ]);
    AbsensiSiswa::create([
        'sekolah_id' => $siswa->sekolah_id,
        'siswa_id' => $siswa->id,
        'date' => $today,
        'status' => 'hadir',
        'method' => 'manual',
        'time_in' => '10:00:00',
    ]);

    expect(AbsensiSiswa::whereDate('date', $today)->where('status', 'hadir')->count())->toBe(2)
        ->and(AttendanceDashboard::distinctStudentsOnDate($today, ['hadir']))->toBe(1)
        ->and(AttendanceDashboard::distinctStudentsByStatus($today)['hadir'])->toBe(1);
});

test('attendance present days this month dedupe multi-slot same day', function () {
    $siswa = $this->fixture->siswa;
    $day = now()->startOfMonth()->addDays(2);

    AbsensiSiswa::create([
        'sekolah_id' => $siswa->sekolah_id,
        'siswa_id' => $siswa->id,
        'date' => $day,
        'status' => 'hadir',
        'method' => 'manual',
    ]);
    AbsensiSiswa::create([
        'sekolah_id' => $siswa->sekolah_id,
        'siswa_id' => $siswa->id,
        'date' => $day,
        'status' => 'hadir',
        'method' => 'manual',
    ]);
    AbsensiSiswa::create([
        'sekolah_id' => $siswa->sekolah_id,
        'siswa_id' => $siswa->id,
        'date' => $day->copy()->addDay(),
        'status' => 'hadir',
        'method' => 'manual',
    ]);

    expect(AttendanceDashboard::presentDayCountThisMonth($siswa->id))->toBe(2);
});

test('tagihan dashboard totals ignore cicilan children', function () {
    $parent = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 200000,
        'paid' => 100000,
        'total_amount' => 300000,
        'is_cicilan' => true,
        'status' => Tagihan::STATUS_CICILAN,
    ]);

    Tagihan::create([
        'sekolah_id' => $parent->sekolah_id,
        'siswa_id' => $parent->siswa_id,
        'tahun_akademik_id' => $parent->tahun_akademik_id,
        'jenis_tagihan_id' => $parent->jenis_tagihan_id,
        'jenis' => $parent->jenis,
        'periode' => $parent->periode,
        'parent_id' => $parent->id,
        'total_amount' => 300000,
        'amount' => 100000,
        'paid' => 100000,
        'status' => Tagihan::STATUS_PAID,
        'is_cicilan' => true,
        'paid_dt' => now(),
        'due_date' => $parent->due_date,
    ]);

    expect((float) Tagihan::sum('paid'))->toBe(200000.0)
        ->and(TagihanDashboard::paidTotal())->toBe(100000.0)
        ->and(TagihanDashboard::billedTotal())->toBe(300000.0)
        ->and(TagihanDashboard::outstandingTotal())->toBe(200000.0)
        ->and(TagihanDashboard::unpaidCount())->toBe(1);
});
