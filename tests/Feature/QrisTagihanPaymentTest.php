<?php

use App\Models\OrangTua;
use App\Models\Pembayaran;
use App\Models\QrisPayment;
use App\Models\User;
use App\Services\Finance\Qris\QrisJwtCodec;
use App\Support\TagihanPeriode;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(PreventRequestForgery::class);

    config([
        'finance.qris.enabled' => true,
        'finance.qris.jwt_key' => 'TokenJWT_BMI_ICT',
        'finance.qris.generate_url' => 'http://103.23.103.43/qris/lazizmu_diy/server.php',
        'finance.qris.account_no' => '5080010295',
        'finance.qris.mitra_customer_id' => 'ISLAMIC CENTER SMG451061',
        'finance.qris.vano_prefix' => '880088',
    ]);

    $this->fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '1000201',
            'name' => 'Siswa QRIS',
        ],
    ]);
    $this->admin = FinanceFixtures::adminUser();
});

function fakeQrisGenerateResponse(string $qrisId = '85655030', int $amount = 500000): array
{
    return [
        'responseCode' => '00',
        'responseMessage' => 'Success',
        'responseTimestamp' => now()->format('Y-m-d H:i:s.v'),
        'transactionDetail' => [
            'accountNo' => '5080010295',
            'amount' => (string) $amount,
            'expiredTime' => now()->addMinutes(30)->format('Y-m-d H:i:s'),
            'merchantId' => '736150800102952',
            'merchantPan' => '936001472400123141',
            'rawQrData' => '00020101021226740022ID.CO.BANKMUAMALAT.WWW0118936001472400123141',
            'transactionQrId' => $qrisId,
        ],
        'transactionId' => '29814378',
    ];
}

test('admin can generate qris for unpaid tagihan', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        'amount' => 500000,
    ]);

    Http::fake([
        '103.23.103.43/*' => Http::response(fakeQrisGenerateResponse('QRADMIN01', 500000), 200),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.qris.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'tagihan_ids' => [$tagihan->id],
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.qris.qris_id', 'QRADMIN01')
        ->assertJsonPath('data.qris.status', 'pending');

    expect(QrisPayment::count())->toBe(1);
    $qris = QrisPayment::first();
    expect($qris->siswa_id)->toBe($this->fixture->siswa->id)
        ->and((float) $qris->amount)->toBe(500000.0)
        ->and($qris->items()->count())->toBe(1)
        ->and($qris->raw_qr_data)->not->toBeEmpty();

    expect($response->json('data.qris.raw_qr_data'))->toContain('000201');

    // Lazismu JWT decode breaks when Laravel asJson() sends body "[]".
    Http::assertSent(function ($request) {
        return str_contains($request->url(), '103.23.103.43/qris/lazizmu_diy/server.php')
            && str_contains($request->url(), 'token=')
            && $request->body() === '';
    });
});

test('push notif settles tagihan like cashier payment', function () {
    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'periode' => TagihanPeriode::fromCalendarMonth(2026, 8),
        'amount' => 250000,
    ]);

    Http::fake([
        '103.23.103.43/*' => Http::response(fakeQrisGenerateResponse('QRPUSH001', 250000), 200),
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.qris.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'tagihan_ids' => [$tagihan->id],
        ])
        ->assertCreated();

    $qris = QrisPayment::first();
    $codec = app(QrisJwtCodec::class);
    $token = $codec->encode([
        'responseCode' => '00',
        'responseMessage' => 'Success',
        'responseTimestamp' => now()->format('Y-m-d H:i:s'),
        'transactionId' => 'PG-999',
        'data' => [
            'vano1' => $qris->vano,
            'amount' => '250000',
            'accountNo' => '5080010295',
            'transactionQrId' => $qris->qris_id,
            'description' => 'SPP',
        ],
    ]);

    $this->getJson('/api/finance/qris/push-notif?token='.urlencode($token))
        ->assertOk()
        ->assertJsonPath('responseCode', '00')
        ->assertJsonPath('processed', 'new_processing');

    expect($tagihan->fresh()->isPaid())->toBeTrue();
    expect(Pembayaran::count())->toBe(1);

    $payment = Pembayaran::first();
    expect($payment->method)->toBe('qris')
        ->and($payment->reference)->toBe($qris->qris_id)
        ->and((float) $payment->total_amount)->toBe(250000.0);

    expect($qris->fresh()->isPaid())->toBeTrue()
        ->and($qris->fresh()->pembayaran_id)->toBe($payment->id);

    // Idempotent second push
    $this->getJson('/api/finance/qris/push-notif?token='.urlencode($token))
        ->assertOk()
        ->assertJsonPath('processed', 'already_processed');

    expect(Pembayaran::count())->toBe(1);
});

test('ortu cannot generate qris for unrelated child', function () {
    $other = FinanceFixtures::schoolWithStudent([
        'sekolah' => ['code' => 'mb', 'name' => 'Other'],
        'siswa' => ['nis' => '2000202', 'name' => 'Anak Lain'],
    ]);
    $tagihan = FinanceFixtures::tagihan($other->siswa, $other->spp, $other->tahun, [
        'amount' => 100000,
    ]);

    $ortu = OrangTua::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'nama_ayah' => 'Ayah QR',
        'telepon_ayah' => '081234567890',
        'status' => 'aktif',
    ]);
    $ortu->siswa()->attach($this->fixture->siswa->id);

    $user = User::create([
        'name' => 'Ortu QR',
        'username' => 'ortu_qris',
        'email' => 'ortu_qris@example.com',
        'password' => bcrypt('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->fixture->sekolah->id,
        'orang_tua_id' => $ortu->id,
    ]);
    $user->assignRole('orang_tua');

    Http::fake();

    $this->actingAs($user)
        ->postJson(route('portal.ortu.tagihan.qris.store'), [
            'siswa_id' => $other->siswa->id,
            'tagihan_ids' => [$tagihan->id],
        ])
        ->assertForbidden();

    expect(QrisPayment::count())->toBe(0);
});

test('generate is blocked when qris disabled', function () {
    config(['finance.qris.enabled' => false]);

    $tagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 100000,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.pembayaran.qris.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'tagihan_ids' => [$tagihan->id],
        ])
        ->assertUnprocessable();
});
