<?php

use App\Models\Dompet;
use App\Models\PengajuanUangSaku;
use App\Models\TransaksiCashless;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->fixture = FinanceFixtures::schoolWithStudent([
        'siswa' => ['nis' => '1000201', 'name' => 'Siswa Wallet'],
    ]);
    $this->admin = FinanceFixtures::adminUser(['sekolah_id' => $this->fixture->sekolah->id]);
});

test('transfer kantin moves wallets and writes audit in one transaction', function () {
    Dompet::create([
        'siswa_id' => $this->fixture->siswa->id,
        'saldo_us' => 50000,
        'saldo_kantin' => 0,
        'saldo_tabungan' => 0,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.transfer-kantin.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 15000,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $dompet = Dompet::where('siswa_id', $this->fixture->siswa->id)->first();

    expect((float) $dompet->saldo_us)->toBe(35000.0)
        ->and((float) $dompet->saldo_kantin)->toBe(15000.0)
        ->and(TransaksiCashless::where('siswa_id', $this->fixture->siswa->id)->where('type', 'transfer')->count())->toBe(1);
});

test('transfer kantin rejects insufficient saldo without partial writes', function () {
    Dompet::create([
        'siswa_id' => $this->fixture->siswa->id,
        'saldo_us' => 5000,
        'saldo_kantin' => 1000,
        'saldo_tabungan' => 0,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.dompet-digital.transfer-kantin.store'), [
            'siswa_id' => $this->fixture->siswa->id,
            'amount' => 15000,
        ])
        ->assertStatus(422);

    $dompet = Dompet::where('siswa_id', $this->fixture->siswa->id)->first();

    expect((float) $dompet->saldo_us)->toBe(5000.0)
        ->and((float) $dompet->saldo_kantin)->toBe(1000.0)
        ->and(TransaksiCashless::where('type', 'transfer')->count())->toBe(0);
});

test('approve pengajuan credits wallet and updates status atomically', function () {
    Dompet::create([
        'siswa_id' => $this->fixture->siswa->id,
        'saldo_us' => 10000,
        'saldo_kantin' => 0,
        'saldo_tabungan' => 0,
    ]);

    $pengajuan = PengajuanUangSaku::create([
        'siswa_id' => $this->fixture->siswa->id,
        'amount' => 25000,
        'reason' => 'Tambahan uang saku',
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.dompet-digital.pengajuan-tambahan.approve', $pengajuan))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect((float) Dompet::where('siswa_id', $this->fixture->siswa->id)->value('saldo_us'))->toBe(35000.0)
        ->and($pengajuan->fresh()->status)->toBe('disetujui')
        ->and(TransaksiCashless::where('siswa_id', $this->fixture->siswa->id)->where('category', 'pengajuan')->count())->toBe(1);
});

test('approve pengajuan rejects already processed request', function () {
    $pengajuan = PengajuanUangSaku::create([
        'siswa_id' => $this->fixture->siswa->id,
        'amount' => 10000,
        'reason' => 'Sudah diproses',
        'status' => 'disetujui',
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.dompet-digital.pengajuan-tambahan.approve', $pengajuan))
        ->assertStatus(422);

    expect(Dompet::where('siswa_id', $this->fixture->siswa->id)->exists())->toBeFalse()
        ->and(TransaksiCashless::where('category', 'pengajuan')->count())->toBe(0);
});
