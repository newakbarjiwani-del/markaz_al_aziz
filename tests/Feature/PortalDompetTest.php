<?php

use App\Models\OrangTua;
use App\Models\SccttranCashless;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();

    $this->fixture = FinanceFixtures::schoolWithStudent();

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'nama_ayah' => 'Bapak Portal',
        'nama_ibu' => 'Ibu Portal',
        'status' => 'aktif',
    ]);
    $this->orangTua->siswa()->attach($this->fixture->siswa->id);

    $this->ortuUser = User::create([
        'username' => 'ortu.dompet',
        'name' => $this->orangTua->displayName(),
        'email' => 'ortu-dompet@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'orang_tua_id' => $this->orangTua->id,
    ]);
    $this->ortuUser->assignRole('orang_tua');

    SccttranCashless::create([
        'CUSTID' => $this->fixture->siswa->id,
        'TRXDATE' => now(),
        'METODE' => 'TOP UP',
        'wallet' => null,
        'KREDIT' => 75000,
        'DEBET' => 0,
        'NOREFF' => 'ORTU-TEST-001',
    ]);
});

test('portal ortu dompet data scopes sccttran cashless by custid', function () {
    $response = $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.dompet.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk()
        ->assertJsonPath('recordsTotal', 1);

    expect(collect($response->json('data'))->first()[4])->toBe('TOP UP');
});

test('portal ortu dompet index shows balance from sccttran cashless ledger', function () {
    $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.dompet.index'))
        ->assertOk()
        ->assertSee('Rp 75.000', false)
        ->assertSee('Detail Transaksi Cashless', false);
});

test('portal ortu can view cashless transaction detail for linked child', function () {
    $trx = SccttranCashless::query()->where('NOREFF', 'ORTU-TEST-001')->firstOrFail();

    $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.dompet.show', $trx))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.metode', 'TOP UP')
        ->assertJsonPath('data.kredit', 75000)
        ->assertJsonPath('data.noreff', 'ORTU-TEST-001')
        ->assertJsonPath('data.siswa.nis', $this->fixture->siswa->nis);
});

test('portal ortu cannot view cashless transaction for unlinked child', function () {
    $other = FinanceFixtures::schoolWithStudent([
        'sekolah' => ['code' => 'mts', 'name' => 'MTs Test'],
        'siswa' => ['nis' => '2000099', 'name' => 'Siswa Lain'],
        'spp' => ['name' => 'SPP MTs', 'is_spp' => false],
        'tahun' => ['name' => '2024/2025', 'is_active' => false],
    ]);

    $trx = SccttranCashless::create([
        'CUSTID' => $other->siswa->id,
        'TRXDATE' => now(),
        'METODE' => 'BELANJA',
        'wallet' => 'us',
        'KREDIT' => 0,
        'DEBET' => 10000,
        'NOREFF' => 'ORTU-OTHER-001',
    ]);

    $this->actingAs($this->ortuUser)
        ->getJson(route('portal.ortu.dompet.show', $trx))
        ->assertForbidden();
});

test('portal siswa dompet data scopes sccttran cashless to linked student', function () {
    $siswaUser = User::create([
        'username' => 'siswa.dompet',
        'name' => $this->fixture->siswa->name,
        'email' => 'siswa-dompet@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'siswa_id' => $this->fixture->siswa->id,
    ]);
    $siswaUser->assignRole('siswa');

    $response = $this->actingAs($siswaUser)
        ->getJson(route('portal.siswa.dompet.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk()
        ->assertJsonPath('recordsTotal', 1);

    expect(collect($response->json('data'))->first()[1])->toBe('TOP UP');
});

test('portal siswa can view own cashless transaction detail', function () {
    $siswaUser = User::create([
        'username' => 'siswa.dompet.detail',
        'name' => $this->fixture->siswa->name,
        'email' => 'siswa-dompet-detail@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'siswa_id' => $this->fixture->siswa->id,
    ]);
    $siswaUser->assignRole('siswa');

    $trx = SccttranCashless::query()->where('NOREFF', 'ORTU-TEST-001')->firstOrFail();

    $this->actingAs($siswaUser)
        ->getJson(route('portal.siswa.dompet.show', $trx))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.metode', 'TOP UP')
        ->assertJsonPath('data.kredit', 75000)
        ->assertJsonPath('data.siswa.nis', $this->fixture->siswa->nis);
});

test('portal siswa cannot view another students cashless transaction', function () {
    $siswaUser = User::create([
        'username' => 'siswa.dompet.other',
        'name' => $this->fixture->siswa->name,
        'email' => 'siswa-dompet-other@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'siswa_id' => $this->fixture->siswa->id,
    ]);
    $siswaUser->assignRole('siswa');

    $other = FinanceFixtures::schoolWithStudent([
        'sekolah' => ['code' => 'mts', 'name' => 'MTs Test'],
        'siswa' => ['nis' => '2000100', 'name' => 'Siswa Lain 2'],
        'spp' => ['name' => 'SPP MTs', 'is_spp' => false],
        'tahun' => ['name' => '2024/2025', 'is_active' => false],
    ]);

    $trx = SccttranCashless::create([
        'CUSTID' => $other->siswa->id,
        'TRXDATE' => now(),
        'METODE' => 'BELANJA',
        'wallet' => 'us',
        'KREDIT' => 0,
        'DEBET' => 5000,
        'NOREFF' => 'SISWA-OTHER-001',
    ]);

    $this->actingAs($siswaUser)
        ->getJson(route('portal.siswa.dompet.show', $trx))
        ->assertForbidden();
});
