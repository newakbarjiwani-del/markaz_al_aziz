<?php

use App\Models\Kelas;
use App\Models\LimitCashless;
use App\Models\PengaturanCashless;
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

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    PengaturanCashless::create([
        'sekolah_id' => $this->sekolah->id,
        'daily_transaction_limit' => 50000,
        'min_topup' => 10000,
        'allow_transfer' => true,
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000001',
        'name' => 'Siswa Limit',
        'status' => Siswa::STATUS_ACTIVE,
        'daily_transaction_limit' => null,
    ]);

    $this->admin = User::create([
        'username' => 'admin.limit',
        'name' => 'Admin Limit',
        'email' => 'admin-limit@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('limit kontrol shows global and per-student sections', function () {
    LimitCashless::create([
        'sekolah_id' => $this->sekolah->id,
        'type' => 'sekolah',
        'target' => 'Semua Siswa',
        'category' => 'makanan',
        'daily_limit' => 50000,
        'monthly_limit' => 500000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dompet-digital.limit-kontrol'))
        ->assertOk()
        ->assertSee('Limit Global', false)
        ->assertSee('Limit Harian Siswa', false)
        ->assertSee('50.000', false)
        ->assertSee('Kontrol RFID', false)
        ->assertDontSee('cashless-pin-modal', false)
        ->assertDontSee('Limit mingguan', false)
        ->assertDontSee('Limit bulanan', false)
        ->assertDontSee('Limit Kategori', false)
        ->assertDontSee('Daftar Limit Kategori', false);
});

test('limit kontrol updates single global daily limit', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.limit-kontrol.global'), [
            'daily_transaction_limit' => 75000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('pengaturan_cashless', [
        'sekolah_id' => $this->sekolah->id,
        'daily_transaction_limit' => 75000,
    ]);
});

test('limit kontrol lists students and updates per-student daily limit', function () {
    $this->siswa->update(['daily_transaction_limit' => 25000]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.dompet-digital.limit-kontrol.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);

    $this->actingAs($this->admin)
        ->putJson(route('admin.dompet-digital.limit-kontrol.update-siswa', $this->siswa), [
            'daily_transaction_limit' => 40000,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('siswa', [
        'id' => $this->siswa->id,
        'daily_transaction_limit' => 40000,
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.dompet-digital.limit-kontrol.update-siswa', $this->siswa), [
            'daily_transaction_limit' => null,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->siswa->fresh()->daily_transaction_limit)->toBeNull();
});

test('rfid kontrol can update uid and block unblock', function () {
    $this->siswa->update([
        'daily_transaction_limit' => 30000,
    ]);
    assignRfid($this->siswa, 'RFID1001');

    $this->actingAs($this->admin)
        ->get(route('admin.dompet-digital.rfid-kontrol'))
        ->assertOk()
        ->assertSee('Kontrol RFID', false)
        ->assertSee('RFID Siswa', false)
        ->assertSee('PIN cashless', false)
        ->assertSee('cashless-pin-modal', false)
        ->assertDontSee('Limit Transaksi Harian', false);

    $this->actingAs($this->admin)
        ->getJson(route('admin.dompet-digital.rfid-kontrol.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertSee('data-cashless-pin-edit', false)
        ->assertSee('Ubah UID kartu RFID', false)
        ->assertSee('btn-action-label', false)
        ->assertSee('UID', false)
        ->assertSee('Blokir', false)
        ->assertSee('Set PIN', false);

    $this->actingAs($this->admin)
        ->putJson(route('admin.dompet-digital.rfid-kontrol.update-rfid', $this->siswa), [
            'rfid_uid' => 'RFID-UPDATED-01',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->siswa->fresh()->rfidUid())->toBe('RFID-UPDATED-01');

    $this->actingAs($this->admin)
        ->patchJson(route('admin.dompet-digital.rfid-kontrol.update-block', $this->siswa), [
            'rfid_blocked' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $fresh = $this->siswa->fresh();
    expect($fresh->isRfidBlocked())->toBeTrue()
        ->and($fresh->rfidUid())->toBe('RFID-UPDATED-01')
        ->and((float) $fresh->daily_transaction_limit)->toBe(30000.0);
});
