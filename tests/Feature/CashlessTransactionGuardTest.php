<?php

use App\Models\PengaturanCashless;
use App\Models\SccttranCashless;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Services\CashlessTransactionGuard;
use App\Support\CashlessPin;
use App\Support\CashlessSettings;
use App\Support\RfidUid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Tigamaya',
    ]);

    PengaturanCashless::create([
        'sekolah_id' => $this->sekolah->id,
        'daily_transaction_limit' => 50000,
        'min_topup' => 10000,
        'allow_transfer' => true,
    ]);
});

function createCashlessStudent(array $overrides = []): Siswa
{
    $uid = $overrides['rfid_uid'] ?? 'S01'.str_pad((string) rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
    $blocked = (bool) ($overrides['rfid_blocked'] ?? false);
    unset($overrides['rfid_uid'], $overrides['rfid_blocked']);

    $siswa = Siswa::create(array_merge([
        'sekolah_id' => test()->sekolah->id,
        'nis' => (string) rand(1000000, 9999999),
        'name' => 'Siswa Cashless',
        'status' => Siswa::STATUS_ACTIVE,
    ], $overrides));

    assignRfid($siswa, $uid, $blocked);

    return $siswa;
}

test('student daily limit is checked before global limit', function () {
    $siswa = createCashlessStudent([
        'daily_transaction_limit' => 30000,
    ]);

    SccttranCashless::create([
        'CUSTID' => $siswa->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => 20000,
        'wallet' => 'kantin',
    ]);

    $guard = app(CashlessTransactionGuard::class);

    $guard->assertCanSpend($siswa, 5000);

    try {
        $guard->assertCanSpend($siswa, 15000);
        expect(false)->toBeTrue('Expected student limit validation failure.');
    } catch (ValidationException $e) {
        expect($e->errors()['amount'][0])->toContain('limit harian siswa');
    }
});

test('global daily limit applies when student limit is not set', function () {
    $siswa = createCashlessStudent([
        'daily_transaction_limit' => null,
    ]);

    SccttranCashless::create([
        'CUSTID' => $siswa->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => 45000,
        'wallet' => 'kantin',
    ]);

    $guard = app(CashlessTransactionGuard::class);

    try {
        $guard->assertCanSpend($siswa, 10000);
        expect(false)->toBeTrue('Expected global limit validation failure.');
    } catch (ValidationException $e) {
        expect($e->errors()['amount'][0])->toContain('limit harian sekolah');
    }
});

test('blocked rfid cannot spend', function () {
    $siswa = createCashlessStudent([
        'rfid_blocked' => true,
    ]);

    $guard = app(CashlessTransactionGuard::class);

    try {
        $guard->assertCanSpend($siswa, 1000);
        expect(false)->toBeTrue('Expected blocked RFID validation failure.');
    } catch (ValidationException $e) {
        expect($e->errors()['rfid_uid'][0])->toContain('diblokir');
    }
});

test('inactive student cannot spend', function () {
    $siswa = createCashlessStudent([
        'status' => Siswa::STATUS_INACTIVE,
    ]);

    $guard = app(CashlessTransactionGuard::class);

    try {
        $guard->assertCanSpend($siswa, 1000);
        expect(false)->toBeTrue('Expected inactive student validation failure.');
    } catch (ValidationException $e) {
        expect($e->errors()['rfid_uid'][0])->toContain('tidak aktif');
    }
});

test('cashless settings reads global daily transaction limit from database', function () {
    expect(CashlessSettings::dailyTransactionLimit($this->sekolah->id))->toBe(50000.0);
});

test('cashless settings can be updated in database', function () {
    CashlessSettings::updateForSekolah($this->sekolah->id, [
        'daily_transaction_limit' => 75000,
    ]);

    expect(CashlessSettings::dailyTransactionLimit($this->sekolah->id))->toBe(75000.0);
});

test('rfid uid is generated from nis and sekolah', function () {
    expect(RfidUid::fromNis('12345', 1))->toBe('S010000012345');
});

test('wouldExceedDailyLimit mirrors student and global limit rules', function () {
    $siswa = createCashlessStudent([
        'daily_transaction_limit' => 10000,
    ]);

    $guard = app(CashlessTransactionGuard::class);

    expect($guard->wouldExceedDailyLimit($siswa, 5000))->toBeFalse();
    expect($guard->wouldExceedDailyLimit($siswa, 10001))->toBeTrue();

    $siswaNoStudentLimit = createCashlessStudent([
        'daily_transaction_limit' => null,
    ]);
    SccttranCashless::create([
        'CUSTID' => $siswaNoStudentLimit->id,
        'METODE' => 'BELANJA',
        'TRXDATE' => now(),
        'DEBET' => 45000,
        'wallet' => 'kantin',
    ]);

    expect($guard->wouldExceedDailyLimit($siswaNoStudentLimit, 10000))->toBeTrue();
    expect($guard->wouldExceedDailyLimit($siswaNoStudentLimit, 4000))->toBeFalse();
});

test('assertWithdrawPin allows under-limit without pin', function () {
    $siswa = createCashlessStudent([
        'daily_transaction_limit' => 20000,
    ]);

    $guard = app(CashlessTransactionGuard::class);
    $guard->assertWithdrawPin($siswa, 5000, null);

    expect(true)->toBeTrue();
});

test('assertWithdrawPin requires set pin when over limit', function () {
    $siswa = createCashlessStudent([
        'daily_transaction_limit' => 5000,
    ]);

    $guard = app(CashlessTransactionGuard::class);

    try {
        $guard->assertWithdrawPin($siswa, 10000, null);
        expect(false)->toBeTrue('Expected unset PIN validation failure.');
    } catch (ValidationException $e) {
        expect($e->errors()['pin'][0])->toContain('Set PIN cashless');
    }
});

test('assertWithdrawPin verifies four digit pin when over limit', function () {
    $siswa = createCashlessStudent([
        'daily_transaction_limit' => 5000,
    ]);
    $siswa->forceFill([
        'cashless_pin' => CashlessPin::hash('4321'),
    ])->save();

    $guard = app(CashlessTransactionGuard::class);

    try {
        $guard->assertWithdrawPin($siswa, 10000, '1111');
        expect(false)->toBeTrue('Expected wrong PIN validation failure.');
    } catch (ValidationException $e) {
        expect($e->errors()['pin'][0])->toBe('PIN salah.');
    }

    $guard->assertWithdrawPin($siswa, 10000, '4321');
});
