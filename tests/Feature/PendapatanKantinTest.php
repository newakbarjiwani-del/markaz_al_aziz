<?php

use App\Models\Dompet;
use App\Models\PenarikanPendapatanKantin;
use App\Models\SccttranCashless;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Cashless\PendapatanKantinWithdrawService;
use App\Support\UserStatus;
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
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.pendapatan',
        'name' => 'Admin Pendapatan',
        'email' => 'admin-pendapatan@local.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);
    $this->admin->assignRole('admin');

    $this->kantin = User::create([
        'username' => 'kantin.pendapatan',
        'name' => 'Operator Kantin A',
        'email' => 'kantin-pendapatan@local.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->kantin->assignRole('kantin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'nis' => '1000999',
        'name' => 'Siswa Belanja',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($this->siswa, 'RFID-PENDAPATAN-1');

    Dompet::create([
        'siswa_id' => $this->siswa->id,
        'saldo_us' => 50000,
    ]);
});

function seedBelanja(User $kantin, Siswa $siswa, int $amount = 20000): void
{
    SccttranCashless::create([
        'CUSTID' => $siswa->id,
        'user_id' => $kantin->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => $amount,
        'wallet' => 'us',
        'description' => 'Nasi goreng',
    ]);
}

test('admin pendapatan kantin lists operators with belanja income and outstanding', function () {
    SccttranCashless::create([
        'CUSTID' => $this->siswa->id,
        'user_id' => $this->kantin->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => 15000,
        'wallet' => 'us',
        'description' => 'Nasi goreng',
    ]);

    SccttranCashless::create([
        'CUSTID' => $this->siswa->id,
        'user_id' => $this->kantin->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => 5000,
        'wallet' => 'kantin',
        'description' => 'Es teh',
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.dompet-digital.pendapatan-kantin.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
        ->assertOk();

    expect($response->json('recordsTotal'))->toBeGreaterThanOrEqual(1);

    $row = collect($response->json('data'))->first(function ($row) {
        $username = is_array($row[1] ?? null) ? ($row[1]['display'] ?? '') : ($row[1] ?? '');

        return str_contains((string) $username, 'kantin.pendapatan');
    });

    expect($row)->not->toBeNull();

    $pendapatan = is_array($row[3]) ? $row[3]['raw'] : $row[3];
    $transaksi = is_array($row[4]) ? $row[4]['raw'] : $row[4];
    $outstanding = is_array($row[5]) ? $row[5]['raw'] : $row[5];

    expect((float) $pendapatan)->toBe(20000.0)
        ->and((int) $transaksi)->toBe(2)
        ->and((int) $outstanding)->toBe(20000);
});

test('admin can view pendapatan detail transactions for kantin operator', function () {
    SccttranCashless::create([
        'CUSTID' => $this->siswa->id,
        'user_id' => $this->kantin->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => 12000,
        'wallet' => 'us',
        'description' => 'Mie ayam',
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.dompet-digital.pendapatan-kantin.show', $this->kantin))
        ->assertOk()
        ->assertJsonPath('data.username', 'kantin.pendapatan')
        ->assertJsonPath('data.outstanding', 12000);

    $this->actingAs($this->admin)
        ->getJson(route('admin.dompet-digital.pendapatan-kantin.transactions', $this->kantin), [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ])
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});

test('school scoped admin only sees own sekolah kantin operators', function () {
    $otherSchool = Sekolah::create([
        'code' => 'mts',
        'name' => 'MTs Test',
        'address' => 'B',
        'is_active' => true,
    ]);

    $otherKantin = User::create([
        'username' => 'kantin.other',
        'name' => 'Operator Lain',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
        'sekolah_id' => $otherSchool->id,
    ]);
    $otherKantin->assignRole('kantin');

    $scopedAdmin = User::create([
        'username' => 'admin.ma',
        'name' => 'Admin MA',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
        'sekolah_id' => $this->sekolah->id,
    ]);
    $scopedAdmin->assignRole('admin');

    $response = $this->actingAs($scopedAdmin)
        ->getJson(route('admin.dompet-digital.pendapatan-kantin.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
        ]))
        ->assertOk();

    $usernames = collect($response->json('data'))->map(function ($row) {
        return is_array($row[1] ?? null) ? ($row[1]['display'] ?? '') : ($row[1] ?? '');
    })->implode(' ');

    expect($usernames)->toContain('kantin.pendapatan')
        ->and($usernames)->not->toContain('kantin.other');
});

test('admin can withdraw pendapatan kantin cash without touching cashless ledger', function () {
    seedBelanja($this->kantin, $this->siswa, 25000);

    $cashlessBefore = SccttranCashless::count();

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.pendapatan-kantin.withdraw', $this->kantin), [
            'amount' => 10000,
            'description' => 'Setoran kas harian',
        ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 10000)
        ->assertJsonPath('data.outstanding', 15000);

    expect(SccttranCashless::count())->toBe($cashlessBefore)
        ->and(PenarikanPendapatanKantin::count())->toBe(1);

    $row = PenarikanPendapatanKantin::first();
    expect($row->method)->toBe(PenarikanPendapatanKantin::METHOD_CASH)
        ->and($row->kantin_user_id)->toBe($this->kantin->id)
        ->and($row->settled_by)->toBe($this->admin->id)
        ->and($row->description)->toBe('Setoran kas harian');
});

test('withdraw rejects amount greater than outstanding', function () {
    seedBelanja($this->kantin, $this->siswa, 5000);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.pendapatan-kantin.withdraw', $this->kantin), [
            'amount' => 6000,
        ])
        ->assertStatus(422);

    expect(PenarikanPendapatanKantin::count())->toBe(0);
});

test('void penarikan restores outstanding', function () {
    seedBelanja($this->kantin, $this->siswa, 20000);

    $service = app(PendapatanKantinWithdrawService::class);
    $penarikan = $service->withdraw($this->kantin, $this->admin, ['amount' => 8000]);

    expect($service->outstandingFor($this->kantin))->toBe(12000);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.dompet-digital.pendapatan-kantin.penarikan.destroy', $penarikan))
        ->assertOk();

    expect($service->outstandingFor($this->kantin->fresh()))->toBe(20000)
        ->and(PenarikanPendapatanKantin::count())->toBe(0)
        ->and(PenarikanPendapatanKantin::withTrashed()->count())->toBe(1);
});

test('kantin portal role cannot withdraw pendapatan', function () {
    seedBelanja($this->kantin, $this->siswa, 10000);

    $this->actingAs($this->kantin)
        ->postJson(route('admin.dompet-digital.pendapatan-kantin.withdraw', $this->kantin), [
            'amount' => 1000,
        ])
        ->assertForbidden();
});

test('cashless role can withdraw and list riwayat penarikan', function () {
    seedBelanja($this->kantin, $this->siswa, 15000);

    $cashlessUser = User::create([
        'username' => 'cashless.op',
        'name' => 'Operator Cashless',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
        'sekolah_id' => $this->sekolah->id,
    ]);
    $cashlessUser->assignRole('cashless');

    $this->actingAs($cashlessUser)
        ->postJson(route('admin.dompet-digital.pendapatan-kantin.withdraw', $this->kantin), [
            'amount' => 5000,
        ])
        ->assertCreated();

    $this->actingAs($cashlessUser)
        ->getJson(route('admin.dompet-digital.pendapatan-kantin.penarikan.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});

test('school scoped admin cannot withdraw other school kantin', function () {
    $otherSchool = Sekolah::create([
        'code' => 'mts',
        'name' => 'MTs Test',
        'address' => 'B',
        'is_active' => true,
    ]);

    $otherKantin = User::create([
        'username' => 'kantin.mts',
        'name' => 'Kantin MTs',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
        'sekolah_id' => $otherSchool->id,
    ]);
    $otherKantin->assignRole('kantin');

    seedBelanja($otherKantin, $this->siswa, 9000);

    $scopedAdmin = User::create([
        'username' => 'admin.ma.scope',
        'name' => 'Admin MA Scope',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
        'sekolah_id' => $this->sekolah->id,
    ]);
    $scopedAdmin->assignRole('admin');

    $this->actingAs($scopedAdmin)
        ->postJson(route('admin.dompet-digital.pendapatan-kantin.withdraw', $otherKantin), [
            'amount' => 1000,
        ])
        ->assertNotFound();
});
