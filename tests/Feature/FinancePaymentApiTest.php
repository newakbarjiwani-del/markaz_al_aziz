<?php

use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\SaldoKeuangan;
use App\Models\Sccttran;
use App\Models\Tagihan;
use App\Services\Finance\SccttranLogger;
use App\Support\TagihanPeriode;
use App\Support\VirtualAccountNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;
use Tests\Support\FinanceJwtTestHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'finance.jwt_key' => 'finance-test-secret',
        'finance.jwt_algo' => 'HS256',
        'finance.payment_mode' => 'auto_loop',
        'finance.biaya_admin' => 0,
        'school.va_prefix' => '770000',
    ]);

    $this->fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '12345',
            'name' => 'Siswa Finance API',
        ],
    ]);

    $this->vano = VirtualAccountNumber::fromNis('12345');
});

function financeToken(array $overrides = []): string
{
    return FinanceJwtTestHelper::encode(array_merge([
        'VANO' => test()->vano,
        'METHOD' => 'INQUIRY',
        'nbf' => time() - 60,
        'iat' => time(),
        'exp' => time() + 3600,
    ], $overrides));
}

/**
 * @return array<string, mixed>
 */
function financeApi(array $payload): array
{
    $response = test()->getJson('/api/finance/payment?' . http_build_query($payload))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');

    return FinanceJwtTestHelper::decode((string) $response->getContent());
}

test('inquiry returns first unpaid bill in minor units as jwt', function () {
    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'jenis' => 'SPP Juli',
        'amount' => 500000,
        'due_date' => '2026-07-01',
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
    ]);

    $payload = financeApi(['token' => financeToken(['METHOD' => 'INQUIRY'])]);

    expect($payload['ERR'])->toBe('00')
        ->and($payload['METHOD'])->toBe('INQUIRY')
        ->and($payload['BILL'])->toBe('50000000')
        ->and($payload['DESCRIPTION'])->toBe('2025/2026')
        ->and($payload['DESCRIPTION2'])->toBe('SPP Juli')
        ->and($payload['CUSTNAME'])->toBe('Siswa Finance API');
});

test('inquiry includes biaya admin in bill amount', function () {
    config(['finance.biaya_admin' => 2500]);

    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 100000,
    ]);

    $payload = financeApi(['token' => financeToken(['METHOD' => 'INQUIRY'])]);

    expect($payload['BILL'])->toBe('10250000');
});

test('inquiry returns err 00 with bill 0 when no unpaid bill', function () {
    $payload = financeApi(['token' => financeToken(['METHOD' => 'INQUIRY'])]);

    expect($payload['ERR'])->toBe('00')
        ->and($payload['METHOD'])->toBe('INQUIRY')
        ->and($payload['BILL'])->toBe('0')
        ->and($payload['DESCRIPTION'])->toBe('')
        ->and($payload['DESCRIPTION2'])->toBe('')
        ->and($payload['CUSTNAME'])->toBe('Siswa Finance API');
});

test('payment tops up saldo and auto loops eligible bills', function () {
    $billA = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'jenis' => 'SPP Juli',
        'amount' => 500000,
        'due_date' => '2026-07-01',
    ]);
    $billB = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'jenis' => 'SPP Agustus',
        'amount' => 500000,
        'due_date' => '2026-08-01',
    ]);
    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'jenis' => 'Uang Gedung',
        'amount' => 1000000,
        'due_date' => '2026-09-01',
    ]);

    $payload = financeApi(['token' => financeToken([
        'METHOD' => 'PAYMENT',
        'PAYMENT' => 1500000,
        'REFNO' => 'REF-AUTO-001',
        'TRXDATE' => '2026-07-07 10:00:00',
        'CHANNELID' => '001',
        'KODEBANK' => '014',
    ])]);

    expect($payload['ERR'])->toBe('00')
        ->and($payload['METHOD'])->toBe('PAYMENT')
        ->and($payload['BILL'])->toBe(1500000)
        ->and($payload['DESCRIPTION'])->toBe('2025/2026')
        ->and($payload['DESCRIPTION2'])->toBe('SPP Juli');

    $saldo = SaldoKeuangan::where('siswa_id', $this->fixture->siswa->id)->first();
    expect((float) $saldo->balance)->toBe(500000.0);

    expect($billA->fresh()->isPaid())->toBeTrue();
    expect($billB->fresh()->isPaid())->toBeTrue();
    expect(Pembayaran::count())->toBe(1);
    expect(PembayaranDetail::count())->toBe(2);
    expect(Sccttran::where('METODE', 'TOP UP')->count())->toBe(1);
    expect(Sccttran::where('METODE', 'FROM INVOICE')->count())->toBe(2);
    expect(Sccttran::where('NOREFF', 'REF-AUTO-001')->count())->toBe(3);

    $payment = Pembayaran::with('details')->first();
    expect($payment->siswa_id)->toBe($this->fixture->siswa->id)
        ->and($payment->user_id)->toBeNull()
        ->and($payment->method)->toBe('va')
        ->and($payment->reference)->toBe('REF-AUTO-001')
        ->and((float) $payment->total_amount)->toBe(1000000.0)
        ->and($payment->paid_dt?->format('Y-m-d H:i:s'))->toBe('2026-07-07 10:00:00');

    expect($billA->fresh()->reference)->toBe('REF-AUTO-001')
        ->and($billA->fresh()->fidbank)->toBe('014')
        ->and($billA->fresh()->user_id)->toBeNull()
        ->and($billA->fresh()->sccttran_id)->toBe($payment->details->firstWhere('tagihan_id', $billA->id)?->sccttran_id);

    expect($billB->fresh()->reference)->toBe('REF-AUTO-001')
        ->and($billB->fresh()->fidbank)->toBe('014');
});

test('payment single mode pays only first eligible bill', function () {
    config(['finance.payment_mode' => 'single']);

    $billA = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'jenis' => 'SPP Juli',
        'amount' => 500000,
        'due_date' => '2026-07-01',
    ]);
    $billB = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'jenis' => 'SPP Agustus',
        'amount' => 500000,
        'due_date' => '2026-08-01',
    ]);

    $payload = financeApi(['token' => financeToken([
        'METHOD' => 'PAYMENT',
        'PAYMENT' => 1500000,
        'REFNO' => 'REF-SINGLE-001',
        'TRXDATE' => '2026-07-07 10:00:00',
    ])]);

    expect($payload['ERR'])->toBe('00');

    $saldo = SaldoKeuangan::where('siswa_id', $this->fixture->siswa->id)->first();
    expect((float) $saldo->balance)->toBe(1000000.0);
    expect($billA->fresh()->isPaid())->toBeTrue();
    expect($billB->fresh()->isUnpaid())->toBeTrue();
    expect(Pembayaran::count())->toBe(1);
    expect(PembayaranDetail::count())->toBe(1);

    $payment = Pembayaran::first();
    expect($payment->reference)->toBe('REF-SINGLE-001')
        ->and((float) $payment->total_amount)->toBe(500000.0);

    expect($billA->fresh()->reference)->toBe('REF-SINGLE-001')
        ->and($billA->fresh()->fidbank)->toBe('va')
        ->and($billA->fresh()->user_id)->toBeNull();
});

test('payment deducts biaya admin before crediting saldo', function () {
    config(['finance.biaya_admin' => 2500]);

    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 500000,
    ]);

    $payload = financeApi(['token' => financeToken([
        'METHOD' => 'PAYMENT',
        'PAYMENT' => 502500,
        'REFNO' => 'REF-FEE-001',
    ])]);

    expect($payload['ERR'])->toBe('00')
        ->and($payload['BILL'])->toBe(502500);

    $saldo = SaldoKeuangan::where('siswa_id', $this->fixture->siswa->id)->first();
    expect((float) $saldo->balance)->toBe(0.0);
    expect(Sccttran::where('METODE', 'TOP UP')->value('KREDIT'))->toBe(500000);

    $payment = Pembayaran::with('details')->first();
    expect($payment->reference)->toBe('REF-FEE-001')
        ->and((float) $payment->total_amount)->toBe(500000.0);

    $bill = Tagihan::paid()->first();
    expect($bill?->reference)->toBe('REF-FEE-001')
        ->and($bill?->fidbank)->toBe('va');
});

test('payment rejects duplicate refno with err 15', function () {
    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 100000,
    ]);

    $token = financeToken([
        'METHOD' => 'PAYMENT',
        'PAYMENT' => 100000,
        'REFNO' => 'REF-DUP-001',
    ]);

    expect(financeApi(['token' => $token])['ERR'])->toBe('00');
    expect(financeApi(['token' => $token])['ERR'])->toBe('15');
});

test('payment returns err 00 when top up succeeds but no bills can be paid', function () {
    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 500000,
        'due_date' => '2026-07-01',
    ]);

    $payload = financeApi(['token' => financeToken([
        'METHOD' => 'PAYMENT',
        'PAYMENT' => 100000,
        'REFNO' => 'REF-TOPUP-ONLY',
    ])]);

    expect($payload['ERR'])->toBe('00')
        ->and($payload['DESCRIPTION'])->toBe('')
        ->and($payload['DESCRIPTION2'])->toBe('');

    $saldo = SaldoKeuangan::where('siswa_id', $this->fixture->siswa->id)->first();
    expect((float) $saldo->balance)->toBe(100000.0);
    expect(Pembayaran::count())->toBe(0);
    expect(Sccttran::where('METODE', 'TOP UP')->count())->toBe(1);
    expect(Sccttran::where('METODE', 'FROM INVOICE')->count())->toBe(0);
});

test('payment returns err 00 when top up succeeds with no unpaid bills', function () {
    $payload = financeApi(['token' => financeToken([
        'METHOD' => 'PAYMENT',
        'PAYMENT' => 100000,
        'REFNO' => 'REF-NO-BILLS',
    ])]);

    expect($payload['ERR'])->toBe('00')
        ->and($payload['DESCRIPTION'])->toBe('');

    $saldo = SaldoKeuangan::where('siswa_id', $this->fixture->siswa->id)->first();
    expect((float) $saldo->balance)->toBe(100000.0);
    expect(Pembayaran::count())->toBe(0);
});

test('payment failure returns err 15 response', function () {
    $this->mock(SccttranLogger::class, function ($mock) {
        $mock->shouldReceive('topUpRefExists')->andReturn(false);
        $mock->shouldReceive('topUp')->andThrow(new RuntimeException('Simulated payment failure'));
    });

    $payload = financeApi(['token' => financeToken([
        'METHOD' => 'PAYMENT',
        'PAYMENT' => 100000,
        'REFNO' => 'REF-FAIL-001',
        'CHANNELID' => '001',
    ])]);

    expect($payload['ERR'])->toBe('15')
        ->and($payload['METHOD'])->toBe('PAYMENT')
        ->and($payload['BILL'])->toBe('')
        ->and($payload['DESCRIPTION'])->toBe('')
        ->and($payload['DESCRIPTION2'])->toBe('')
        ->and($payload['CUSTNAME'])->toBe('')
        ->and($payload['err'])->toBe('Simulated payment failure');
});

test('reversal returns minimal success response as jwt', function () {
    $payload = financeApi(['token' => financeToken(['METHOD' => 'REVERSAL'])]);

    expect($payload['ERR'])->toBe('00')
        ->and($payload['METHOD'])->toBe('REVERSAL')
        ->and($payload['CUSTNAME'])->toBe('Siswa Finance API')
        ->and($payload)->not->toHaveKeys(['BILL', 'DESCRIPTION', 'DESCRIPTION2']);
});

test('invalid jwt returns err 15', function () {
    $response = $this->getJson('/api/finance/payment?token=invalid.token.value')
        ->assertOk();

    $payload = FinanceJwtTestHelper::decode((string) $response->getContent());

    expect($payload['ERR'])->toBe('15')
        ->and($payload['METHOD'])->toBe('PAYMENT');
});
