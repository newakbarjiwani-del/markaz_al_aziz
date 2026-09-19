<?php

use App\Models\Kelas;
use App\Models\JenisPelanggaran;
use App\Models\PelanggaranSiswa;
use App\Models\PrestasiSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\ImportSpreadsheetPreviewCache;
use App\Support\ImportStoreMethod;
use App\Support\SiswaCatatanSpreadsheetTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'Madrasah Aliyah (MA)',
        'address' => 'Tigamaya',
    ]);

    $this->admin = User::create([
        'username' => 'admin.prestasi',
        'name' => 'Admin Prestasi',
        'email' => 'admin-prestasi@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->importKelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas' => 'X',
        'kelompok' => '01',
        'name' => 'X 01',
        'unit' => 'MA',
        'jenjang' => 'X',
        'is_active' => true,
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->importKelas->id,
        'nis' => '9001001',
        'name' => 'AHMAD PRESTASI',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);
});

function sampleCatatanRow(
    string $nis = '9001001',
    string $judul = 'Juara 1 Lomba Matematika',
    string $keterangan = 'Tingkat kabupaten',
    string $tanggal = '2026-07-15',
    string $point = '10',
): array {
    return [$nis, $judul, $keterangan, $tanggal, $point];
}

function makeCatatanImportFile(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([SiswaCatatanSpreadsheetTemplate::COLUMNS, ...$rows]);

    $path = tempnam(sys_get_temp_dir(), 'catatan-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'format-input-catatan-siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

function confirmCatatanImport(User $user, string $type, string $method = ImportStoreMethod::CREATE_AND_UPDATE): \Illuminate\Testing\TestResponse
{
    return test()->actingAs($user)->post(route("admin.prestasi-pelanggaran.impor-{$type}.confirm"), [
        'method' => $method,
    ]);
}

test('admin can download prestasi import template generated from code', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.prestasi-pelanggaran.impor-prestasi.template'))
        ->assertOk()
        ->assertDownload('Format Input Prestasi Siswa.xlsx');

    $sheet = SiswaCatatanSpreadsheetTemplate::buildSpreadsheet()->getActiveSheet();
    $headers = $sheet->rangeToArray('A1:E1', null, true, true, false)[0];

    expect($headers)->toBe(SiswaCatatanSpreadsheetTemplate::COLUMNS);
});

test('admin can download pelanggaran import template', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.prestasi-pelanggaran.impor-pelanggaran.template'))
        ->assertOk()
        ->assertDownload('Format Input Pelanggaran Siswa.xlsx');
});

test('admin can preview prestasi import with valid and invalid rows', function () {
    $file = makeCatatanImportFile([
        sampleCatatanRow(),
        sampleCatatanRow(nis: '9999999', judul: 'Siswa tidak ada'),
        sampleCatatanRow(judul: '', keterangan: 'Judul kosong'),
        sampleCatatanRow(tanggal: 'bukan-tanggal'),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.prestasi-pelanggaran.impor-prestasi.preview'), ['file' => $file])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.preview.summary.will_create', 1)
        ->assertJsonPath('data.preview.summary.invalid', 3)
        ->assertJsonPath('data.preview.rows.0.status', 1)
        ->assertJsonPath('data.preview.rows.0.display.nama', 'AHMAD PRESTASI')
        ->assertJsonPath('data.preview.rows.1.status', 0)
        ->assertJsonPath('data.preview.rows.1.description', 'Siswa dengan NIS 9999999 tidak ditemukan di sekolah ini.')
        ->assertJsonPath('data.preview.rows.2.status', 0)
        ->assertJsonPath('data.preview.rows.2.description', 'Judul wajib diisi.')
        ->assertJsonPath('data.preview.rows.3.status', 0);

    expect(ImportSpreadsheetPreviewCache::has($this->admin->id, 'prestasi'))->toBeTrue();
});

test('preview marks duplicate siswa+judul+tanggal rows invalid', function () {
    $file = makeCatatanImportFile([
        sampleCatatanRow(),
        sampleCatatanRow(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.prestasi-pelanggaran.impor-prestasi.preview'), ['file' => $file])
        ->assertOk()
        ->assertJsonPath('data.preview.summary.will_create', 1)
        ->assertJsonPath('data.preview.summary.invalid', 1)
        ->assertJsonPath('data.preview.rows.1.status', 0)
        ->assertJsonPath('data.preview.rows.1.description', fn ($description) => str_contains($description, 'Duplikat baris'));
});

test('preview accepts dd/mm/yyyy date format and empty point', function () {
    $file = makeCatatanImportFile([
        sampleCatatanRow(tanggal: '15/07/2026', point: ''),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.prestasi-pelanggaran.impor-prestasi.preview'), ['file' => $file])
        ->assertOk()
        ->assertJsonPath('data.preview.summary.will_create', 1)
        ->assertJsonPath('data.preview.rows.0.display.tanggal', '2026-07-15')
        ->assertJsonPath('data.preview.rows.0.display.point', '0');
});

test('admin can confirm prestasi import from cached preview', function () {
    $file = makeCatatanImportFile([
        sampleCatatanRow(),
        sampleCatatanRow(judul: 'Hafalan 5 Juz', keterangan: 'Pencapaian tahfidz', tanggal: '15/07/2026', point: ''),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.prestasi-pelanggaran.impor-prestasi.preview'), ['file' => $file])
        ->assertOk();

    confirmCatatanImport($this->admin, 'prestasi')
        ->assertOk()
        ->assertJsonPath('data.created', 2);

    expect(PrestasiSiswa::count())->toBe(2)
        ->and(PrestasiSiswa::where('siswa_id', $this->siswa->id)->where('judul', 'Juara 1 Lomba Matematika')->value('tanggal')?->format('Y-m-d'))->toBe('2026-07-15')
        ->and(PrestasiSiswa::where('judul', 'Hafalan 5 Juz')->value('point'))->toBe(0)
        ->and(PrestasiSiswa::where('judul', 'Hafalan 5 Juz')->value('keterangan'))->toBe('Pencapaian tahfidz')
        ->and(PrestasiSiswa::where('judul', 'Juara 1 Lomba Matematika')->value('reported_by'))->toBeNull();

    expect(ImportSpreadsheetPreviewCache::has($this->admin->id, 'prestasi'))->toBeFalse();
});

test('admin can confirm pelanggaran import from cached preview', function () {
    $file = makeCatatanImportFile([
        sampleCatatanRow(judul: 'Terlambat masuk kelas'),
        sampleCatatanRow(nis: '9999999', judul: 'Tidak akan tersimpan'),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.prestasi-pelanggaran.impor-pelanggaran.preview'), ['file' => $file])
        ->assertOk();

    confirmCatatanImport($this->admin, 'pelanggaran')
        ->assertOk()
        ->assertJsonPath('data.created', 1)
        ->assertJsonPath('data.skipped', 1);

    expect(PelanggaranSiswa::count())->toBe(1)
        ->and(PelanggaranSiswa::where('judul', 'Terlambat masuk kelas')->value('siswa_id'))->toBe($this->siswa->id)
        ->and(PelanggaranSiswa::where('judul', 'Terlambat masuk kelas')->value('sekolah_id'))->toBe($this->sekolah->id);

    expect(ImportSpreadsheetPreviewCache::has($this->admin->id, 'pelanggaran'))->toBeFalse();
});

test('pelanggaran import resolves jenis pelanggaran catalog and point', function () {
    $jenis = JenisPelanggaran::create([
        'level' => 'ringan',
        'bidang' => 'Kedisiplinan',
        'nama' => 'Terlambat Masuk Kelas Import',
        'point' => 7,
        'is_active' => true,
    ]);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        SiswaCatatanSpreadsheetTemplate::PELANGGARAN_COLUMNS,
        ['9001001', $jenis->nama, '', 'Datang terlambat', '2026-07-20', ''],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'pelanggaran-jenis-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $file = new UploadedFile($path, 'format-pelanggaran-jenis.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $this->actingAs($this->admin)
        ->post(route('admin.prestasi-pelanggaran.impor-pelanggaran.preview'), ['file' => $file])
        ->assertOk()
        ->assertJsonPath('data.preview.summary.will_create', 1)
        ->assertJsonPath('data.preview.rows.0.display.judul', $jenis->nama)
        ->assertJsonPath('data.preview.rows.0.display.point', '7');

    confirmCatatanImport($this->admin, 'pelanggaran')
        ->assertOk()
        ->assertJsonPath('data.created', 1);

    expect(PelanggaranSiswa::where('judul', $jenis->nama)->value('jenis_pelanggaran_id'))->toBe($jenis->id)
        ->and(PelanggaranSiswa::where('judul', $jenis->nama)->value('point'))->toBe(7);
});

test('admin can view prestasi and pelanggaran import index pages', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.prestasi-pelanggaran.impor-prestasi'))
        ->assertOk()
        ->assertSee('Import Prestasi Siswa')
        ->assertSee('Unduh Template Excel');

    $this->actingAs($this->admin)
        ->get(route('admin.prestasi-pelanggaran.impor-pelanggaran'))
        ->assertOk()
        ->assertSee('Import Pelanggaran Siswa')
        ->assertSee('Unduh Template Excel');
});
