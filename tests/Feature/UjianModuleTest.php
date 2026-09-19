<?php

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAkademik;
use App\Models\Ujian;
use App\Models\UjianAttempt;
use App\Models\UjianSoal;
use App\Models\User;
use App\Support\AkademikSemester;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ujn',
        'name' => 'Sekolah Ujian Test',
        'address' => 'Jl. Test',
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'VIII A',
        'unit' => 'SMP',
        'is_active' => true,
    ]);

    $this->otherKelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'VIII B',
        'unit' => 'SMP',
        'is_active' => true,
    ]);

    $this->tahun = TahunAkademik::create([
        'name' => '2026/2027',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.ujian',
        'name' => 'Admin Ujian',
        'email' => 'admin-ujian@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '20262001',
        'name' => 'Siswa Ujian',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->siswaUser = User::create([
        'username' => 'siswa.ujian',
        'name' => 'Siswa Portal Ujian',
        'email' => 'siswa-ujian@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'siswa_id' => $this->siswa->id,
    ]);
    $this->siswaUser->assignRole('siswa');

    $this->mapel = MataPelajaran::create([
        'sekolah_id' => $this->sekolah->id,
        'code' => 'MTK',
        'name' => 'Matematika',
        'is_active' => true,
    ]);
});

test('admin can create ujian soal and publish', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.ujian.ujian.store'), [
            'title' => 'UTS Matematika',
            'tahun_akademik_id' => $this->tahun->id,
            'semester' => AkademikSemester::GANJIL,
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas_id' => $this->kelas->id,
            'sekolah_id' => $this->sekolah->id,
            'starts_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_attempts' => 1,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $ujian = Ujian::query()->first();
    expect($ujian)->not->toBeNull()
        ->and($ujian->status)->toBe(Ujian::STATUS_DRAFT);

    $this->actingAs($this->admin)
        ->postJson(route('admin.ujian.ujian.soal.store', $ujian), [
            'jenis' => UjianSoal::JENIS_PILIHAN_GANDA,
            'pertanyaan' => '2 + 2 = ?',
            'poin' => 10,
            'opsi' => [
                ['key' => 'A', 'label' => '3'],
                ['key' => 'B', 'label' => '4'],
                ['key' => 'C', 'label' => '5'],
                ['key' => 'D', 'label' => '6'],
            ],
            'kunci' => 'B',
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.ujian.ujian.publish', $ujian))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($ujian->fresh()->status)->toBe(Ujian::STATUS_PUBLISHED);
});

test('siswa can start submit and mcq is scored; second attempt blocked', function () {
    $ujian = Ujian::create([
        'sekolah_id' => $this->sekolah->id,
        'tahun_akademik_id' => $this->tahun->id,
        'semester' => AkademikSemester::GANJIL,
        'mata_pelajaran_id' => $this->mapel->id,
        'kelas_id' => $this->kelas->id,
        'title' => 'Kuis Pendek',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'status' => Ujian::STATUS_PUBLISHED,
        'max_attempts' => 1,
    ]);

    $soal = $ujian->soal()->create([
        'sort_order' => 1,
        'jenis' => UjianSoal::JENIS_PILIHAN_GANDA,
        'pertanyaan' => 'Ibukota Indonesia?',
        'poin' => 10,
        'opsi' => [
            ['key' => 'A', 'label' => 'Jakarta'],
            ['key' => 'B', 'label' => 'Bandung'],
            ['key' => 'C', 'label' => 'Surabaya'],
            ['key' => 'D', 'label' => 'Medan'],
        ],
        'kunci' => 'A',
    ]);

    $this->actingAs($this->siswaUser)
        ->post(route('portal.siswa.ujian.start', $ujian))
        ->assertRedirect(route('portal.siswa.ujian.show', $ujian));

    $this->actingAs($this->siswaUser)
        ->post(route('portal.siswa.ujian.submit', $ujian), [
            'answers' => [$soal->id => 'A'],
        ])
        ->assertRedirect(route('portal.siswa.ujian.show', $ujian));

    $attempt = UjianAttempt::query()->first();
    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe(UjianAttempt::STATUS_SUBMITTED)
        ->and((float) $attempt->skor_mcq)->toBe(10.0)
        ->and((float) $attempt->skor_max_mcq)->toBe(10.0);

    $this->actingAs($this->siswaUser)
        ->post(route('portal.siswa.ujian.start', $ujian))
        ->assertSessionHasErrors('attempt');
});

test('draft ujian is not listed on portal', function () {
    Ujian::create([
        'sekolah_id' => $this->sekolah->id,
        'tahun_akademik_id' => $this->tahun->id,
        'semester' => AkademikSemester::GANJIL,
        'kelas_id' => $this->kelas->id,
        'title' => 'Draft Rahasia',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'status' => Ujian::STATUS_DRAFT,
        'max_attempts' => 1,
    ]);

    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.ujian.index'))
        ->assertOk()
        ->assertDontSee('Draft Rahasia');
});

test('siswa from wrong kelas cannot open ujian', function () {
    $ujian = Ujian::create([
        'sekolah_id' => $this->sekolah->id,
        'tahun_akademik_id' => $this->tahun->id,
        'semester' => AkademikSemester::GANJIL,
        'kelas_id' => $this->otherKelas->id,
        'title' => 'Ujian Kelas Lain',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'status' => Ujian::STATUS_PUBLISHED,
        'max_attempts' => 1,
    ]);

    $ujian->soal()->create([
        'sort_order' => 1,
        'jenis' => UjianSoal::JENIS_ESSAY,
        'pertanyaan' => 'Tuliskan nama lengkap Anda.',
        'poin' => 5,
    ]);

    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.ujian.show', $ujian))
        ->assertForbidden();
});
