<?php

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

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'jenjang' => 'X',
        'is_active' => true,
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000001',
        'name' => 'Siswa Kiosk',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($this->siswa, 'RFIDKIOSK01');

    $this->admin = User::create([
        'username' => 'admin.kiosk',
        'name' => 'Admin Kiosk',
        'email' => 'admin-kiosk@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('saldo rfid kiosk page loads for cashless.view and hides finance panel', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dompet-digital.saldo-rfid.index'))
        ->assertOk()
        ->assertSee('Kiosk Saldo RFID', false)
        ->assertSee('data-finance-enabled="0"', false)
        ->assertSee('id="finance-balance-panel"', false)
        ->assertSee('saldo-rfid-kiosk.js', false);

    $js = file_get_contents(public_path('js/saldo-rfid-kiosk.js'));
    expect($js)->not->toContain('saldo-siswa')
        ->and($js)->not->toContain('finance/balance')
        ->and($js)->not->toContain('SccttranSaldo');
});

test('saldo rfid lookup returns cashless balance for known card', function () {
    SccttranCashless::create([
        'CUSTID' => $this->siswa->id,
        'user_id' => $this->admin->id,
        'METODE' => 'TOP UP',
        'TRXDATE' => now(),
        'NOREFF' => 'KIOSKTOPUP1',
        'KREDIT' => 15000,
        'DEBET' => 0,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.saldo-rfid.lookup'), [
            'rfid_uid' => 'RFIDKIOSK01',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.cashless_balance', 15000)
        ->assertJsonPath('data.siswa.nis', '1000001')
        ->assertJsonMissingPath('data.finance_balance');
});

test('saldo rfid lookup rejects unknown and inactive siswa', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.saldo-rfid.lookup'), [
            'rfid_uid' => 'UNKNOWNCARD',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    $this->siswa->update(['status' => Siswa::STATUS_INACTIVE]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.saldo-rfid.lookup'), [
            'rfid_uid' => 'RFIDKIOSK01',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Siswa tidak aktif / tidak dapat bertransaksi.');
});
