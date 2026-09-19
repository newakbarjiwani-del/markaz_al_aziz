<?php

use App\Models\JenisTagihan;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Tagihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => [
            'nis' => '1000201',
            'name' => 'Siswa Laporan',
        ],
    ]);
    $this->admin = FinanceFixtures::adminUser();

    $this->seragam = JenisTagihan::create([
        'name' => 'Seragam',
        'default_amount' => 200000,
        'is_spp' => false,
        'is_active' => true,
        'sort_order' => 2,
    ]);
});

test('laporan keuangan page includes jenis tagihan filter options', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.keuangan.laporan-keuangan'))
        ->assertOk()
        ->assertSee('filter-jenis-tagihan', false)
        ->assertSee('SPP', false)
        ->assertSee('Seragam', false);
});

test('laporan keuangan data filters payments by jenis tagihan bill', function () {
    $sppBill = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 300000,
        'paid' => 300000,
        'status' => Tagihan::STATUS_PAID,
    ]);
    $seragamBill = FinanceFixtures::tagihan($this->fixture->siswa, $this->seragam, $this->fixture->tahun, [
        'amount' => 200000,
        'paid' => 200000,
        'status' => Tagihan::STATUS_PAID,
        'periode' => 202602,
    ]);

    $sppPayment = Pembayaran::create([
        'siswa_id' => $this->fixture->siswa->id,
        'method' => 'tunai',
        'reference' => 'KW-SPP-01',
        'total_amount' => 300000,
        'paid_dt' => now(),
    ]);
    PembayaranDetail::create([
        'pembayaran_id' => $sppPayment->id,
        'tagihan_id' => $sppBill->id,
        'amount' => 300000,
    ]);

    $seragamPayment = Pembayaran::create([
        'siswa_id' => $this->fixture->siswa->id,
        'method' => 'tunai',
        'reference' => 'KW-SRG-01',
        'total_amount' => 200000,
        'paid_dt' => now(),
    ]);
    PembayaranDetail::create([
        'pembayaran_id' => $seragamPayment->id,
        'tagihan_id' => $seragamBill->id,
        'amount' => 200000,
    ]);

    $all = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.laporan-keuangan.data'))
        ->assertOk()
        ->json();

    expect($all['recordsTotal'])->toBe(2);

    $filtered = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.laporan-keuangan.data', [
            'jenis_tagihan_id' => $this->fixture->spp->id,
        ]))
        ->assertOk()
        ->json();

    expect($filtered['recordsFiltered'])->toBe(1);

    $payload = json_encode($filtered['data']);
    expect($payload)->toContain('KW-SPP-01')
        ->and($payload)->not->toContain('KW-SRG-01');
});
