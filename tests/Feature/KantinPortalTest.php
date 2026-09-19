<?php

use App\Models\Dompet;
use App\Models\Kelas;
use App\Models\SccttranCashless;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);
});

test('kantin user with sekolah can access portal dashboard', function () {
    $kantin = User::create([
        'username' => 'kantin_ok',
        'name' => 'Operator Kantin',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantin->assignRole('kantin');

    $this->actingAs($kantin)
        ->get(route('portal.kantin.dashboard'))
        ->assertOk();
});

test('kantin user without sekolah can access portal dashboard', function () {
    $kantin = User::create([
        'username' => 'kantin_all',
        'name' => 'Operator Semua Sekolah',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $kantin->assignRole('kantin');

    $this->actingAs($kantin)
        ->get(route('portal.kantin.dashboard'))
        ->assertOk();
});

test('scoped kantin operator cannot charge student from another school', function () {
    $otherSchool = Sekolah::create([
        'code' => 'mts',
        'name' => 'MTs Test',
        'address' => 'Jl. Other',
    ]);

    $siswa = Siswa::create([
        'sekolah_id' => $otherSchool->id,
        'nis' => '2000001',
        'name' => 'Siswa MTs',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($siswa, 'RFID-MTS-001');

    $kantin = User::create([
        'username' => 'kantin_ma',
        'name' => 'Operator MA',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantin->assignRole('kantin');

    $this->actingAs($kantin)
        ->postJson(route('portal.kantin.pos.charge'), [
            'rfid_uid' => $siswa->rfidUid(),
            'amount' => 5000,
        ])
        ->assertForbidden();
});

test('super admin can create kantin user without sekolah', function () {
    $superAdmin = User::create([
        'username' => 'superadmin',
        'name' => 'Super Admin',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)
        ->postJson(route('super-admin.users.store'), [
            'username' => 'kantin_no_school',
            'name' => 'Kantin No School',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'aktif',
            'role' => ['kantin'],
        ])
        ->assertCreated();

    expect(User::where('username', 'kantin_no_school')->first()?->sekolah_id)->toBeNull();
});

test('kantin pos lookup returns student and cashless balance', function () {
    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000501',
        'name' => 'Siswa Kantin',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($siswa, 'RFID-KANTIN-001');

    Dompet::create([
        'siswa_id' => $siswa->id,
        'saldo_us' => 10000,
        'saldo_kantin' => 15000,
    ]);

    SccttranCashless::create([
        'CUSTID' => $siswa->id,
        'METODE' => 'TOP UP',
        'TRXDATE' => now(),
        'KREDIT' => 25000,
        'wallet' => 'us',
    ]);

    $kantin = User::create([
        'username' => 'kantin_pos',
        'name' => 'Operator POS',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantin->assignRole('kantin');

    $this->actingAs($kantin)
        ->postJson(route('portal.kantin.pos.lookup'), [
            'rfid_uid' => 'RFID-KANTIN-001',
        ])
        ->assertOk()
        ->assertJsonPath('data.siswa.name', 'Siswa Kantin')
        ->assertJsonPath('data.saldo_cashless', 25000);
});

test('kantin pos charge debits cashless balance and stores description', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000502',
        'name' => 'Siswa Belanja',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($siswa, 'RFID-KANTIN-002');

    Dompet::create([
        'siswa_id' => $siswa->id,
        'saldo_us' => 30000,
    ]);

    SccttranCashless::create([
        'CUSTID' => $siswa->id,
        'METODE' => 'TOP UP',
        'TRXDATE' => now(),
        'KREDIT' => 30000,
        'wallet' => 'us',
    ]);

    $kantin = User::create([
        'username' => 'kantin_charge',
        'name' => 'Operator Charge',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantin->assignRole('kantin');

    $this->actingAs($kantin)
        ->postJson(route('portal.kantin.pos.charge'), [
            'rfid_uid' => 'RFID-KANTIN-002',
            'amount' => 12000,
            'description' => 'Nasi goreng + es teh',
        ])
        ->assertCreated()
        ->assertJsonPath('data.saldo_cashless', 18000);

    $transaction = SccttranCashless::query()
        ->where('CUSTID', $siswa->id)
        ->where('METODE', 'BELANJA')
        ->latest('id')
        ->first();

    expect($transaction?->DEBET)->toBe(12000)
        ->and($transaction?->wallet)->toBe('us')
        ->and($transaction?->description)->toBe('Nasi goreng + es teh')
        ->and($transaction?->user_id)->toBe($kantin->id)
        ->and((float) $siswa->dompet()->first()?->saldo_us)->toBe(18000.0);
});

test('kantin pos charge spends across wallets starting from uang saku', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000503',
        'name' => 'Siswa Multi Wallet',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($siswa, 'RFID-KANTIN-003');

    Dompet::create([
        'siswa_id' => $siswa->id,
        'saldo_us' => 5000,
        'saldo_kantin' => 10000,
    ]);

    $kantin = User::create([
        'username' => 'kantin_multi',
        'name' => 'Operator Multi',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantin->assignRole('kantin');

    $this->actingAs($kantin)
        ->postJson(route('portal.kantin.pos.charge'), [
            'rfid_uid' => 'RFID-KANTIN-003',
            'amount' => 12000,
            'description' => 'Mie ayam',
        ])
        ->assertCreated()
        ->assertJsonPath('data.saldo_cashless', 3000);

    $dompet = $siswa->dompet()->first();

    expect((float) $dompet?->saldo_us)->toBe(0.0)
        ->and((float) $dompet?->saldo_kantin)->toBe(3000.0);

    $rows = SccttranCashless::query()
        ->where('CUSTID', $siswa->id)
        ->where('METODE', 'BELANJA')
        ->orderBy('id')
        ->get();

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->wallet)->toBe('us')
        ->and($rows[0]->DEBET)->toBe(5000)
        ->and($rows[1]->wallet)->toBe('kantin')
        ->and($rows[1]->DEBET)->toBe(7000);

    $list = $this->actingAs($kantin)
        ->getJson(route('portal.kantin.transaksi.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
        ->assertOk();

    expect($list->json('recordsTotal'))->toBe(2);

    $wallets = collect($list->json('data'))->map(fn (array $row) => $row[4])->all();
    expect($wallets)->toContain('Uang Saku')
        ->and($wallets)->toContain('Kantin');

    $this->actingAs($kantin)
        ->getJson(route('portal.kantin.transaksi.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'wallet' => 'us',
        ]))
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1);
});

test('kantin transaksi data only lists current operator belanja', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000505',
        'name' => 'Siswa Scope',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($siswa, 'RFID-KANTIN-005');

    $kantinA = User::create([
        'username' => 'kantin_a',
        'name' => 'Operator A',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantinA->assignRole('kantin');

    $kantinB = User::create([
        'username' => 'kantin_b',
        'name' => 'Operator B',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantinB->assignRole('kantin');

    SccttranCashless::create([
        'CUSTID' => $siswa->id,
        'user_id' => $kantinA->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => 8000,
        'wallet' => 'us',
        'description' => 'Milik A',
    ]);

    SccttranCashless::create([
        'CUSTID' => $siswa->id,
        'user_id' => $kantinB->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => 9000,
        'wallet' => 'us',
        'description' => 'Milik B',
    ]);

    $this->actingAs($kantinA)
        ->getJson(route('portal.kantin.transaksi.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('data.0.6', 'Milik A');
});

test('kantin pos charge returns hint when saldo is insufficient', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000504',
        'name' => 'Siswa Saldo Tipis',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($siswa, 'RFID-KANTIN-004');

    Dompet::create([
        'siswa_id' => $siswa->id,
        'saldo_us' => 5000,
    ]);

    $kantin = User::create([
        'username' => 'kantin_fail',
        'name' => 'Operator Fail',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $kantin->assignRole('kantin');

    $this->actingAs($kantin)
        ->postJson(route('portal.kantin.pos.charge'), [
            'rfid_uid' => 'RFID-KANTIN-004',
            'amount' => 12000,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('hint', 'Kurangi nominal total belanja, atau minta siswa/ortu melakukan top-up saldo cashless terlebih dahulu.')
        ->assertJsonPath('data.saldo_cashless', 5000)
        ->assertJsonPath('data.amount', 12000);
});
