<?php

use App\Models\Kelas;
use App\Models\SccttranCashless;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\SmTopup;
use App\Models\User;
use App\Support\CashlessPin;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $kelas = Kelas::create([
        'sekolah_id' => $sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'jenjang' => 'X',
        'is_active' => true,
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000001',
        'name' => 'Siswa Saldo',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->admin = User::create([
        'username' => 'admin.saldo',
        'name' => 'Admin Saldo',
        'email' => 'admin-saldo@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('manual topup saldo is blocked by default', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 10000,
        ])
        ->assertForbidden()
        ->assertJsonPath('message', 'Top-up saldo cashless manual sementara dinonaktifkan.');
});

test('manual topup saldo rejects zero amount', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 0,
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Nominal top-up harus lebih dari 0.');
});

test('manual topup saldo writes sccttran cashless and sm_topup when enabled', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 10000,
            'description' => 'Top-up uji',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('sccttran_cashless', [
        'CUSTID' => $this->siswa->id,
        'user_id' => $this->admin->id,
        'METODE' => 'TOP UP',
        'wallet' => null,
        'KREDIT' => 10000,
        'description' => 'Top-up uji',
    ]);

    $this->assertDatabaseHas('sm_topup', [
        'CUSTID' => $this->siswa->id,
        'user_id' => $this->admin->id,
        'NOMINAL' => 10000,
        'TOPUPNO' => SmTopup::TOPUPNO_CASHLESS,
    ]);

    $this->assertDatabaseHas('dompet', [
        'siswa_id' => $this->siswa->id,
        'saldo_us' => 10000,
    ]);
});

test('saldo cashless page includes topup modal', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);

    $this->actingAs($this->admin)
        ->get(route('admin.dompet-digital.saldo-cashless.index'))
        ->assertOk()
        ->assertSee('Top-up Saldo', false)
        ->assertSee('data-open-modal="topup-cashless-modal"', false)
        ->assertSee('id="topup-cashless-modal"', false)
        ->assertSee('id="topup-cashless-form"', false)
        ->assertSee('id="topup-cashless-saldo-info"', false)
        ->assertSee('Tarik Saldo', false)
        ->assertSee('data-open-modal="withdraw-cashless-modal"', false)
        ->assertSee('id="withdraw-cashless-modal"', false)
        ->assertSee('id="withdraw-cashless-form"', false)
        ->assertSee('id="withdraw-cashless-saldo-info"', false)
        ->assertSee('Saldo cashless tersedia', false)
        ->assertSee('Scan RFID', false)
        ->assertSee('data-lookup-rfid-url', false)
        ->assertSee('id="saldo-cashless-topup-rfid"', false)
        ->assertSee('id="saldo-cashless-withdraw-rfid"', false)
        ->assertSee('data-confirm-submit', false)
        ->assertSee('buildCashlessTopupConfirm', false)
        ->assertSee('buildCashlessWithdrawConfirm', false);
});

test('manual withdraw saldo is blocked by default', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 5000,
        ])
        ->assertForbidden()
        ->assertJsonPath('message', 'Tarik saldo cashless manual sementara dinonaktifkan.');
});

test('manual withdraw saldo writes sccttran cashless debit with fidbank cash', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 20000,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 7500,
            'description' => 'Tarik uji',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('sccttran_cashless', [
        'CUSTID' => $this->siswa->id,
        'user_id' => $this->admin->id,
        'METODE' => 'TARIK SALDO',
        'FIDBANK' => 'CASH',
        'DEBET' => 7500,
        'KREDIT' => 0,
        'description' => 'Tarik uji',
    ]);

    $this->assertDatabaseHas('dompet', [
        'siswa_id' => $this->siswa->id,
        'saldo_us' => 12500,
    ]);

    expect(SmTopup::where('CUSTID', $this->siswa->id)->count())->toBe(1);
});

test('manual withdraw saldo rejects insufficient balance', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 1000,
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect(SccttranCashless::where('METODE', 'TARIK SALDO')->count())->toBe(0);
});

test('under-limit withdraw does not require cashless pin', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    $this->siswa->update(['daily_transaction_limit' => 50000]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 100000,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 10000,
        ])
        ->assertCreated();
});

test('over-limit withdraw without pin is rejected when pin unset', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    $this->siswa->update(['daily_transaction_limit' => 5000]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 100000,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 10000,
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonFragment(['message' => 'Tarik saldo melebihi limit harian. Set PIN cashless siswa terlebih dahulu.']);
});

test('over-limit withdraw without pin field is rejected when pin is set', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    $this->siswa->forceFill([
        'daily_transaction_limit' => 5000,
        'cashless_pin' => CashlessPin::hash('1234'),
    ])->save();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 100000,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 10000,
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'PIN wajib diisi karena nominal melebihi limit harian.']);
});

test('over-limit withdraw rejects wrong pin', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    $this->siswa->forceFill([
        'daily_transaction_limit' => 5000,
        'cashless_pin' => CashlessPin::hash('1234'),
    ])->save();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 100000,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 10000,
            'pin' => '9999',
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'PIN salah.']);
});

test('over-limit withdraw succeeds with correct pin', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    $this->siswa->forceFill([
        'daily_transaction_limit' => 5000,
        'cashless_pin' => CashlessPin::hash('1234'),
    ])->save();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 100000,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 10000,
            'pin' => '1234',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('sccttran_cashless', [
        'CUSTID' => $this->siswa->id,
        'METODE' => 'TARIK SALDO',
        'DEBET' => 10000,
    ]);
});

test('withdraw preview reports pin required when over limit', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    $this->siswa->update(['daily_transaction_limit' => 5000]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.dompet-digital.withdraw-saldo.preview', [
            'siswa_id' => $this->siswa->id,
            'amount' => 10000,
        ]))
        ->assertOk()
        ->assertJsonPath('data.would_exceed', true)
        ->assertJsonPath('data.pin_required', true)
        ->assertJsonPath('data.pin_set', false);
});

test('manual topup via rfid writes ledger with fidbank rfid', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    assignRfid($this->siswa, 'RFIDTOPUP01');

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'rfid_uid' => 'RFIDTOPUP01',
            'amount' => 15000,
            'description' => 'Top-up RFID',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('sccttran_cashless', [
        'CUSTID' => $this->siswa->id,
        'METODE' => 'TOP UP',
        'FIDBANK' => 'RFID',
        'KREDIT' => 15000,
        'description' => 'Top-up RFID',
    ]);
});

test('manual withdraw via rfid writes ledger with fidbank rfid', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    assignRfid($this->siswa, 'RFIDWD01');

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'rfid_uid' => 'RFIDWD01',
            'amount' => 20000,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.withdraw-saldo.store'), [
            'rfid_uid' => 'RFIDWD01',
            'amount' => 5000,
            'description' => 'Tarik RFID',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('sccttran_cashless', [
        'CUSTID' => $this->siswa->id,
        'METODE' => 'TARIK SALDO',
        'FIDBANK' => 'RFID',
        'DEBET' => 5000,
        'description' => 'Tarik RFID',
    ]);
});

test('rfid lookup returns siswa and balance for topup withdraw', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    assignRfid($this->siswa, 'RFIDLOOKUP01');

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 8000,
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.lookup-rfid'), [
            'rfid_uid' => 'RFIDLOOKUP01',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.siswa.id', $this->siswa->id)
        ->assertJsonPath('data.siswa.nis', $this->siswa->nis)
        ->assertJsonPath('data.balance', 8000);
});

test('rfid lookup rejects blocked card', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);
    assignRfid($this->siswa, 'RFIDBLOCK01', blocked: true);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.lookup-rfid'), [
            'rfid_uid' => 'RFIDBLOCK01',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('topup saldo data lists sccttran cashless top up rows', function () {
    config(['school.manual_saldo_cashless_adjustment_enabled' => true]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.topup-saldo.store'), [
            'siswa_id' => $this->siswa->id,
            'amount' => 25000,
            'description' => 'Top-up list',
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->getJson(route('admin.dompet-digital.topup-saldo.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('data.0.1', $this->siswa->nis)
        ->assertJsonPath('data.0.2', $this->siswa->name);
});

test('manual saldo adjustment is blocked by default', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.saldo-siswa.adjust'), [
            'siswa_id' => $this->siswa->id,
            'type' => 'tambah',
            'amount' => 10000,
        ])
        ->assertForbidden()
        ->assertJsonPath('message', 'Penyesuaian saldo manual sementara dinonaktifkan.');
});
