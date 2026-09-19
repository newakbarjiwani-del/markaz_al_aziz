<?php

use App\Models\JenisTagihan;
use App\Models\Tagihan;
use App\Services\TagihanSpreadsheetImporter;
use App\Support\ImportSpreadsheetPreviewCache;
use App\Support\ImportStoreMethod;
use App\Support\TagihanPeriode;
use App\Support\TagihanSpreadsheetTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\FinanceFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    FinanceFixtures::seedPermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->fixture = FinanceFixtures::schoolWithStudent();
    $this->admin = FinanceFixtures::adminUser(['sekolah_id' => $this->fixture->sekolah->id]);
});

function sampleTagihanImportRow(
    string $nis = '1000001',
    string $tagihan = '500000',
    string $jenis = 'SPP',
    string $periode = '2025-07',
    string $tahunAkademik = '2025/2026',
): array {
    return [$nis, $tagihan, $jenis, $periode, $tahunAkademik];
}

function makeTagihanImportFile(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(TagihanSpreadsheetTemplate::COLUMNS, null, 'A1');

    if ($rows !== []) {
        $sheet->fromArray($rows, null, 'A2');
    }

    $path = tempnam(sys_get_temp_dir(), 'tagihan-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'tagihan-import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('tagihan import preview validates nis jenis periode tahun akademik and amount', function () {
    $file = makeTagihanImportFile([
        sampleTagihanImportRow(),
        sampleTagihanImportRow('9999999', '500000', 'SPP', '2025-07', '2025/2026'),
        sampleTagihanImportRow('1000001', '', 'SPP', '2025-07', '2025/2026'),
        sampleTagihanImportRow('1000001', '500', 'SPP', '2025-07', '2025/2026'),
        sampleTagihanImportRow('1000001', '500000', 'Tidak Ada', '2025-07', '2025/2026'),
        sampleTagihanImportRow('1000001', '500000', 'SPP', 'invalid', '2025/2026'),
        sampleTagihanImportRow('1000001', '500000', 'SPP', '2026-07', '2025/2026'),
    ]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.keuangan.impor-tagihan.preview'), [
        'file' => $file,
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $rows = $response->json('data.preview.rows');

    expect($rows[0]['status'])->toBe(1)
        ->and($rows[1]['status'])->toBe(0)
        ->and($rows[1]['description'])->toContain('NIS')
        ->and($rows[2]['status'])->toBe(0)
        ->and($rows[2]['description'])->toContain('Nominal')
        ->and($rows[3]['status'])->toBe(0)
        ->and($rows[3]['description'])->toContain('minimal')
        ->and($rows[4]['status'])->toBe(0)
        ->and($rows[4]['description'])->toContain('Jenis tagihan')
        ->and($rows[5]['status'])->toBe(0)
        ->and($rows[5]['description'])->toContain('Periode')
        ->and($rows[6]['status'])->toBe(0)
        ->and($rows[6]['description'])->toContain('tidak sesuai');
});

test('tagihan import preview rejects nis longer than 30 digits', function () {
    $file = makeTagihanImportFile([
        sampleTagihanImportRow(str_repeat('9', 31)),
    ]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.keuangan.impor-tagihan.preview'), [
        'file' => $file,
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    expect($response->json('data.preview.rows.0.status'))->toBe(0)
        ->and($response->json('data.preview.rows.0.description'))->toContain('NIS maksimal 30 digit')
        ->and($response->json('data.preview.summary.invalid'))->toBe(1);
});

test('tagihan import confirm creates bills from preview cache', function () {
    $file = makeTagihanImportFile([
        sampleTagihanImportRow(),
        sampleTagihanImportRow('1000001', '600000', 'SPP', '2026-01', '2025/2026'),
    ]);

    $this->actingAs($this->admin)->postJson(route('admin.keuangan.impor-tagihan.preview'), [
        'file' => $file,
    ])->assertOk();

    $confirm = $this->actingAs($this->admin)->postJson(route('admin.keuangan.impor-tagihan.confirm'), [
        'method' => ImportStoreMethod::CREATE_ONLY,
    ]);

    $confirm->assertOk()
        ->assertJsonPath('data.created', 2)
        ->assertJsonPath('data.updated', 0);

    expect(Tagihan::query()->where('siswa_id', $this->fixture->siswa->id)->count())->toBe(2);

    $july = Tagihan::query()
        ->where('siswa_id', $this->fixture->siswa->id)
        ->where('periode', TagihanPeriode::fromCalendarMonth(2025, 7))
        ->first();

    expect($july)->not->toBeNull()
        ->and((int) $july->amount)->toBe(500000)
        ->and($july->jenis)->toBe('SPP');
});

test('tagihan import updates unpaid bill amount when method allows update', function () {
    $existing = FinanceFixtures::tagihan(
        $this->fixture->siswa,
        $this->fixture->spp,
        $this->fixture->tahun,
        ['amount' => 400000, 'periode' => TagihanPeriode::fromCalendarMonth(2025, 7)]
    );

    $file = makeTagihanImportFile([
        sampleTagihanImportRow('1000001', '550000', 'SPP', '2025-07', '2025/2026'),
    ]);

    $preview = $this->actingAs($this->admin)->postJson(route('admin.keuangan.impor-tagihan.preview'), [
        'file' => $file,
    ]);

    $preview->assertOk()->assertJsonPath('data.preview.summary.will_update', 1);

    $confirm = $this->actingAs($this->admin)->postJson(route('admin.keuangan.impor-tagihan.confirm'), [
        'method' => ImportStoreMethod::UPDATE_ONLY,
    ]);

    $confirm->assertOk()->assertJsonPath('data.updated', 1);

    expect((int) $existing->fresh()->amount)->toBe(550000);
});

test('tagihan import rejects paid bill update', function () {
    FinanceFixtures::tagihan(
        $this->fixture->siswa,
        $this->fixture->spp,
        $this->fixture->tahun,
        [
            'amount' => 500000,
            'paid' => 500000,
            'status' => Tagihan::STATUS_PAID,
            'periode' => TagihanPeriode::fromCalendarMonth(2025, 7),
        ]
    );

    $importer = app(TagihanSpreadsheetImporter::class);
    $preview = $importer->preview($this->fixture->sekolah, [
        [
            'NIS' => '1000001',
            'TAGIHAN' => '600000',
            'JENIS_TAGIHAN' => 'SPP',
            'PERIODE' => '2025-07',
            'TAHUN_AKADEMIK' => '2025/2026',
        ],
    ]);

    expect($preview['rows'][0]['status'])->toBe(0)
        ->and($preview['rows'][0]['description'])->toContain('sudah lunas');
});

test('tagihan import template download is available for finance users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.keuangan.impor-tagihan.template'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('tagihan import page requires finance create permission', function () {
    $viewer = FinanceFixtures::adminUser([
        'username' => 'finance.viewer',
        'email' => 'finance-viewer@test.local',
        'sekolah_id' => $this->fixture->sekolah->id,
    ]);
    $viewer->removeRole('admin');
    $viewer->givePermissionTo('finance.view');

    $this->actingAs($viewer)
        ->postJson(route('admin.keuangan.impor-tagihan.preview'), [
            'file' => makeTagihanImportFile([sampleTagihanImportRow()]),
        ])
        ->assertForbidden();
});

afterEach(function () {
    Cache::flush();
    ImportSpreadsheetPreviewCache::forget((int) ($this->admin->id ?? 0), 'tagihan');
});
