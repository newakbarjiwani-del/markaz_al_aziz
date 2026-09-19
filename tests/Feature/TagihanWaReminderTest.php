<?php

use App\Models\OrangTua;
use App\Models\TemplatePesanTagihan;
use App\Support\PortalGreeting;
use App\Support\TagihanPesanKategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->fixture = FinanceFixtures::schoolWithStudent();
    $this->admin = FinanceFixtures::adminUser(['sekolah_id' => $this->fixture->sekolah->id]);

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'nama_ayah' => 'Ayah Test',
        'telepon_ayah' => '081234567890',
        'status' => 'aktif',
    ]);
    $this->orangTua->siswa()->attach($this->fixture->siswa->id);

    $this->template = TemplatePesanTagihan::create([
        'nama' => 'Test Template',
        'kategori' => TagihanPesanKategori::BELUM_JATUH_TEMPO,
        'isi_pesan' => "Assalamualaikum {nama_anak}\nTotal {jumlah_tagihan}\n{rincian}",
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->tagihanA = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 500000,
        'due_date' => now()->addDays(7),
    ]);
    $this->tagihanB = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 300000,
        'jenis' => 'Uang Gedung',
        'due_date' => now()->addDays(14),
        'periode' => \App\Support\TagihanPeriode::fromCalendarMonth(2026, 8),
    ]);

    $this->otherSiswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->fixture->sekolah->id,
        'kelas_id' => $this->fixture->kelas->id,
        'nis' => '1000002',
        'name' => 'Siswa Lain',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);
    $this->otherTagihan = FinanceFixtures::tagihan($this->otherSiswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 200000,
    ]);
});

test('admin can manage template pesan tagihan wa', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.keuangan.template-pesan-tagihan.index'))
        ->assertOk();

    $this->actingAs($this->admin)
        ->post(route('admin.keuangan.template-pesan-tagihan.store'), [
            'nama' => 'Template Baru',
            'kategori' => TagihanPesanKategori::LEWAT_JATUH_TEMPO,
            'isi_pesan' => 'Pesan {nama_anak} {jumlah_tagihan} {rincian}',
            'sort_order' => 2,
            'is_active' => '1',
        ])
        ->assertCreated();

    $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.template-pesan-tagihan.data'))
        ->assertOk();
});

test('build wa message requires same student and valid phone', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kirim-tagihan-wa.build'), [
            'tagihan_ids' => [$this->tagihanA->id, $this->otherTagihan->id],
            'template_id' => $this->template->id,
            'random_template' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tagihan_ids']);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kirim-tagihan-wa.build'), [
            'tagihan_ids' => [$this->tagihanA->id, $this->tagihanB->id],
            'template_id' => $this->template->id,
            'random_template' => false,
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.siswa_id', $this->fixture->siswa->id)
        ->assertJsonPath('data.total_remaining', 800000);

    expect($response->json('data.whatsapp_url'))->toContain('wa.me/6281234567890')
        ->and($response->json('data.message'))->toContain('Siswa Test')
        ->and($response->json('data.message'))->toContain('Rp 800.000');
});

test('overdue tagihan uses lewat jatuh tempo template category', function () {
    $overdueTemplate = TemplatePesanTagihan::create([
        'nama' => 'Overdue Template',
        'kategori' => TagihanPesanKategori::LEWAT_JATUH_TEMPO,
        'isi_pesan' => 'Lewat tempo {nama_anak} {jumlah_tagihan} {rincian}',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $overdueTagihan = FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 100000,
        'due_date' => now()->subDays(3),
        'periode' => \App\Support\TagihanPeriode::fromCalendarMonth(2026, 6),
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kirim-tagihan-wa.build'), [
            'tagihan_ids' => [$overdueTagihan->id],
            'template_id' => $overdueTemplate->id,
            'random_template' => false,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.kategori', TagihanPesanKategori::LEWAT_JATUH_TEMPO)
        ->assertJsonPath('data.has_overdue', true)
        ->assertJsonPath('data.template_id', $overdueTemplate->id);
});

test('build wa rejects tagihan when siswa is missing or deleted', function () {
    $this->fixture->siswa->delete();

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kirim-tagihan-wa.build'), [
            'tagihan_ids' => [$this->tagihanA->id],
            'template_id' => $this->template->id,
            'random_template' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tagihan_ids']);
});

test('build wa rejects siswa without phone', function () {
    $this->orangTua->update(['telepon_ayah' => null, 'telepon_ibu' => null, 'telepon_wali' => null]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kirim-tagihan-wa.build'), [
            'tagihan_ids' => [$this->tagihanA->id],
            'template_id' => $this->template->id,
            'random_template' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tagihan_ids']);
});

test('build wa message replaces sapaan placeholder by time of day', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-04 08:00:00'));

    $this->template->update([
        'isi_pesan' => '{sapaan} Bapak/Ibu wali {nama_anak}. Total {jumlah_tagihan}.',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.keuangan.kirim-tagihan-wa.build'), [
            'tagihan_ids' => [$this->tagihanA->id],
            'template_id' => $this->template->id,
            'random_template' => false,
        ]);

    $response->assertOk();
    expect($response->json('data.message'))
        ->toContain(PortalGreeting::salutation())
        ->not->toContain('{sapaan}');

    Carbon::setTestNow();
});

test('kirim tagihan wa data filters by student name or nis', function () {
    $match = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.kirim-tagihan-wa.data', ['q' => 'Siswa Test']))
        ->assertOk()
        ->json('recordsFiltered');

    $miss = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.kirim-tagihan-wa.data', ['q' => 'Tidak Ada Siswa']))
        ->assertOk()
        ->json('recordsFiltered');

    expect($match)->toBeGreaterThan(0)
        ->and($miss)->toBe(0);
});

test('kirim tagihan wa data lists unpaid bills with phone badge', function () {
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.kirim-tagihan-wa.data'));

    $response->assertOk();
    $rows = collect($response->json('data'));
    $firstRow = $rows->first();
    $checkboxHtml = (string) data_get($firstRow, '0.display', $firstRow[0] ?? '');
    $nameHtml = (string) data_get($firstRow, '2.display', $firstRow[2] ?? '');
    expect($checkboxHtml)->toContain('tagihan-wa-checkbox')
        ->and($nameHtml)->toContain('WA wali tersedia');
});

test('kirim tagihan wa data excludes tagihan whose siswa is deleted', function () {
    FinanceFixtures::tagihan($this->fixture->siswa, $this->fixture->spp, $this->fixture->tahun, [
        'amount' => 150000,
        'periode' => \App\Support\TagihanPeriode::fromCalendarMonth(2026, 5),
    ]);
    $this->fixture->siswa->delete();

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.keuangan.kirim-tagihan-wa.data'));

    $response->assertOk();
    $rows = collect($response->json('data'));

    expect($rows->first(fn ($row) => ($row[1] ?? null) === '-'))->toBeNull();
    expect($rows->contains(fn ($row) => str_contains((string) data_get($row, '2.display', $row[2] ?? ''), 'Siswa Test')))->toBeFalse();
});
