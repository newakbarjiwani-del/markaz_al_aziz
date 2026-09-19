<?php

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Support\TagihanPeriode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->sekolahA = Sekolah::create(['code' => 'ma', 'name' => 'MA', 'address' => 'A', 'is_active' => true]);
    $this->sekolahB = Sekolah::create(['code' => 'mts', 'name' => 'MTs', 'address' => 'B', 'is_active' => true]);

    $this->kelasA = Kelas::create(['sekolah_id' => $this->sekolahA->id, 'name' => 'X IPA', 'is_active' => true]);
    $this->kelasB = Kelas::create(['sekolah_id' => $this->sekolahB->id, 'name' => 'VII A', 'is_active' => true]);

    $this->tahun = TahunAkademik::create(['name' => '2025/2026', 'is_active' => true]);

    $this->spp = FinanceFixtures::jenisTagihan(null, ['default_amount' => 500000]);

    $this->gedungA = FinanceFixtures::jenisTagihan(null, [
        'name' => 'Uang Gedung',
        'default_amount' => 2000000,
        'is_spp' => false,
    ]);

    $this->siswaA = Siswa::create([
        'sekolah_id' => $this->sekolahA->id,
        'kelas_id' => $this->kelasA->id,
        'nis' => '1001',
        'name' => 'Siswa MA',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $this->siswaB = Siswa::create([
        'sekolah_id' => $this->sekolahB->id,
        'kelas_id' => $this->kelasB->id,
        'nis' => '2001',
        'name' => 'Siswa MTs',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $this->admin = FinanceFixtures::adminUser([
        'username' => 'admin.finance',
        'name' => 'Admin Finance',
        'email' => 'admin-finance@test.local',
    ]);
});

test('create tagihan allows universal jenis and tahun across schools', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.tagihan.store'), [
            'siswa_id' => $this->siswaA->id,
            'tahun_akademik_id' => $this->tahun->id,
            'jenis_tagihan_id' => $this->spp->id,
            'amount' => 500000,
            'periode' => TagihanPeriode::toMonthInput(TagihanPeriode::current()),
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    expect(Tagihan::query()->where('siswa_id', $this->siswaA->id)->where('jenis_tagihan_id', $this->spp->id)->exists())->toBeTrue();
});

test('generate preview counts existing bills for selected jenis, academic year and period', function () {
    $periode = TagihanPeriode::fromCalendarMonth(2026, 7);

    FinanceFixtures::tagihan($this->siswaA, $this->spp, $this->tahun, [
        'periode' => $periode,
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.tagihan.generate-preview'), [
            'tahun_akademik_id' => $this->tahun->id,
            'jenis_tagihan_id' => $this->spp->id,
            'periode' => '2026-07',
        ])
        ->assertOk();

    expect($response->json('data.total'))->toBe(2)
        ->and($response->json('data.skipped'))->toBe(1)
        ->and($response->json('data.eligible'))->toBe(1)
        ->and($response->json('data.all_exist'))->toBeFalse();
});

test('generate tagihan respects class filter and selected jenis', function () {
    $periode = TagihanPeriode::fromCalendarMonth(2026, 8);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.tagihan.generate'), [
            'tahun_akademik_id' => $this->tahun->id,
            'jenis_tagihan_id' => $this->gedungA->id,
            'periode' => '2026-08',
            'kelas_id' => $this->kelasA->id,
        ])
        ->assertOk();

    expect(
        Tagihan::where('siswa_id', $this->siswaA->id)
            ->where('periode', $periode)
            ->where('jenis_tagihan_id', $this->gedungA->id)
            ->count()
    )->toBe(1)
        ->and(Tagihan::where('siswa_id', $this->siswaB->id)->where('periode', $periode)->count())->toBe(0);
});

test('generate SPP bulanan follows jenis month for periode', function () {
    $sppJanuari = FinanceFixtures::jenisTagihan(null, [
        'name' => 'SPP JANUARI',
        'is_spp' => true,
        'default_amount' => 550000,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.tagihan.generate'), [
            'tahun_akademik_id' => $this->tahun->id,
            'jenis_tagihan_id' => $sppJanuari->id,
            'periode' => '2026-08',
            'kelas_id' => $this->kelasA->id,
        ])
        ->assertOk();

    $periodeExpected = TagihanPeriode::fromCalendarMonth(2026, 1);
    expect(
        Tagihan::where('siswa_id', $this->siswaA->id)
            ->where('jenis_tagihan_id', $sppJanuari->id)
            ->value('periode')
    )->toBe($periodeExpected);
});

test('generate detects month name in non spp jenis and sets periode', function () {
    $jenisFebruari = FinanceFixtures::jenisTagihan(null, [
        'name' => 'UANG MAKAN FEBRUARI',
        'is_spp' => false,
        'default_amount' => 120000,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.tagihan.generate'), [
            'tahun_akademik_id' => $this->tahun->id,
            'jenis_tagihan_id' => $jenisFebruari->id,
            'kelas_id' => $this->kelasA->id,
        ])
        ->assertOk();

    $periodeExpected = TagihanPeriode::fromCalendarMonth(2026, 2);
    expect(
        Tagihan::where('siswa_id', $this->siswaA->id)
            ->where('jenis_tagihan_id', $jenisFebruari->id)
            ->value('periode')
    )->toBe($periodeExpected);
});
