<?php

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\StudentSpreadsheetImporter;
use App\Support\ImportSpreadsheetPreviewCache;
use App\Support\ImportStoreMethod;
use App\Support\StudentSpreadsheetTemplate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'Madrasah Aliyah (MA)',
        'address' => 'Tigamaya',
    ]);

    $this->admin = User::create([
        'username' => 'admin.import',
        'name' => 'Admin Import',
        'email' => 'admin-import@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->importKelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas' => 'XII',
        'kelompok' => '01-IBNU HAJAR',
        'name' => 'XII 01-IBNU HAJAR',
        'unit' => 'MA',
        'jenjang' => 'XII',
        'is_active' => true,
    ]);
});

function sampleStudentImportRow(
    string $nis = '9001001',
    string $nama = 'AHMAD IMPORT',
    string $tempatLahir = 'Pekanbaru',
    string $tanggalLahir = '2010-01-15',
    string $alamat = 'Jl. Contoh Pekanbaru',
    string $wali = 'HABIBI',
): array {
    return [
        $nis,
        $nama,
        'NODAF'.$nis,
        'MA',
        '12',
        '01-IBNU HAJAR',
        '2025/2026',
        'L',
        $tempatLahir,
        $tanggalLahir,
        $alamat,
        $wali,
        'aktif',
    ];
}

function makeStudentImportFile(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([StudentSpreadsheetTemplate::COLUMNS, ...$rows]);

    $path = tempnam(sys_get_temp_dir(), 'siswa-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'format-input-siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

function confirmStudentImport(User $user, string $method = ImportStoreMethod::CREATE_AND_UPDATE): TestResponse
{
    return test()->actingAs($user)->post(route('admin.manajemen-siswa.impor-ekspor.confirm'), [
        'method' => $method,
    ]);
}

test('student preview marks row invalid when kelas is not in master data', function () {
    Kelas::query()->where('sekolah_id', $this->sekolah->id)->delete();

    $file = makeStudentImportFile([
        sampleStudentImportRow(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), ['file' => $file])
        ->assertOk()
        ->assertJsonPath('data.preview.summary.invalid', 1)
        ->assertJsonPath('data.preview.rows.0.status', 0)
        ->assertJsonFragment(['description' => 'Kelas «XII 01-IBNU HAJAR» tidak ditemukan di Master Data. Tambahkan kelas terlebih dahulu.']);
});

test('student preview marks row invalid when nis exceeds 30 digits', function () {
    $tooLong = str_repeat('5', 31);
    $file = makeStudentImportFile([
        sampleStudentImportRow($tooLong),
        sampleStudentImportRow('512336041084210044', 'NIS OK PANJANG'),
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), ['file' => $file])
        ->assertOk();

    $rows = $response->json('data.preview.rows');

    expect($rows[0]['status'])->toBe(0)
        ->and($rows[0]['description'])->toContain('NIS maksimal 30 digit')
        ->and($rows[1]['status'])->toBe(1)
        ->and($response->json('data.preview.summary.invalid'))->toBe(1)
        ->and($response->json('data.preview.summary.will_create'))->toBe(1);
});

test('admin can download student import template generated from code', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-siswa.impor-ekspor.template'))
        ->assertOk()
        ->assertDownload('Format Input Siswa.xlsx');

    $path = StudentSpreadsheetTemplate::generateTemplateFile();
    $sheet = IOFactory::load($path)->getActiveSheet();
    $headers = $sheet->rangeToArray('A1:M1', null, true, true, false)[0];

    expect($headers)->toBe(StudentSpreadsheetTemplate::COLUMNS);
});

test('admin can preview student import and store rows in cache', function () {
    $file = makeStudentImportFile([
        sampleStudentImportRow(),
        ['', 'INVALID ROW', 'NODAF002', 'MA', '12', '01-IBNU HAJAR', '2025/2026', 'L', '', '', 'Jl. Contoh', 'WALI', 'aktif'],
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), ['file' => $file])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.preview.summary.will_create', 1)
        ->assertJsonPath('data.preview.summary.invalid', 1)
        ->assertJsonPath('data.preview.rows.0.status', 1)
        ->assertJsonPath('data.preview.rows.1.status', 0);

    expect(ImportSpreadsheetPreviewCache::has($this->admin->id, 'siswa'))->toBeTrue();
});

test('admin can confirm student import from cached preview', function () {
    $file = makeStudentImportFile([
        sampleStudentImportRow(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), ['file' => $file])
        ->assertOk();

    confirmStudentImport($this->admin)
        ->assertOk()
        ->assertJsonPath('data.created', 1);

    $siswa = Siswa::where('nis', '9001001')->first();

    expect($siswa)->not->toBeNull()
        ->and($siswa->name)->toBe('AHMAD IMPORT')
        ->and($siswa->kelas?->kelas)->toBe('XII')
        ->and($siswa->kelas?->kelompok)->toBe('01-IBNU HAJAR')
        ->and($siswa->kelas?->name)->toBe('XII 01-IBNU HAJAR');

    expect(ImportSpreadsheetPreviewCache::has($this->admin->id, 'siswa'))->toBeFalse();
});

test('import updates existing student by nis after preview confirm', function () {
    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'XII Lama',
        'unit' => 'MA',
        'jenjang' => 'XII',
        'is_active' => true,
    ]);

    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '9001002',
        'name' => 'Nama Lama',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $file = makeStudentImportFile([
        sampleStudentImportRow('9001002', 'NAMA BARU', 'Jakarta', '2012-05-17', 'Alamat baru', 'WALI'),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), ['file' => $file])
        ->assertJsonPath('data.preview.rows.0.status', 2);

    confirmStudentImport($this->admin)
        ->assertJsonPath('data.updated', 1);

    expect(Siswa::where('nis', '9001002')->value('name'))->toBe('NAMA BARU');

    $updated = Siswa::where('nis', '9001002')->first();
    expect($updated?->birth_place)->toBe('Jakarta')
        ->and($updated?->birth_date?->format('Y-m-d'))->toBe('2012-05-17');
});

test('student preview includes before after comparison for updates', function () {
    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'XII Lama',
        'unit' => 'MA',
        'jenjang' => 'XII',
        'is_active' => true,
    ]);

    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '9001999',
        'name' => 'Nama Existing',
        'gender' => 'L',
        'birth_place' => 'Pekanbaru',
        'birth_date' => '2011-01-01',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $file = makeStudentImportFile([
        sampleStudentImportRow('9001999', 'Nama Existing Update', 'Bandung', '2011-02-03'),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), ['file' => $file])
        ->assertOk()
        ->assertJsonPath('data.preview.rows.0.status', 2)
        ->assertJsonPath('data.preview.rows.0.comparison.before.birth_place', 'Pekanbaru')
        ->assertJsonPath('data.preview.rows.0.comparison.after.birth_place', 'Bandung');
});

test('create only import method skips existing students', function () {
    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'XII Lama',
        'unit' => 'MA',
        'jenjang' => 'XII',
        'is_active' => true,
    ]);

    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '9001003',
        'name' => 'Nama Lama',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $file = makeStudentImportFile([
        sampleStudentImportRow('9001003', 'NAMA BARU', 'Pekanbaru', '2010-01-01', 'Alamat baru', 'WALI'),
        sampleStudentImportRow('9001004', 'SISWA BARU', 'Pekanbaru', '2010-02-02', 'Alamat baru', 'WALI'),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), ['file' => $file]);

    confirmStudentImport($this->admin, ImportStoreMethod::CREATE_ONLY)
        ->assertJsonPath('data.created', 1)
        ->assertJsonPath('data.skipped', 1);

    expect(Siswa::where('nis', '9001003')->value('name'))->toBe('Nama Lama')
        ->and(Siswa::where('nis', '9001004')->exists())->toBeTrue();
});

test('super admin can preview and confirm student import with sekolah selected', function () {
    $superAdmin = User::create([
        'username' => 'superadmin.import',
        'name' => 'Super Admin Import',
        'email' => 'superadmin-import@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $superAdmin->assignRole('super_admin');

    $file = makeStudentImportFile([
        sampleStudentImportRow('9002001', 'SISWA SUPERADMIN'),
    ]);

    $this->actingAs($superAdmin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), [
            'file' => $file,
            'sekolah_id' => $this->sekolah->id,
        ])
        ->assertOk();

    $this->actingAs($superAdmin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.confirm'), [
            'method' => ImportStoreMethod::CREATE_AND_UPDATE,
            'sekolah_id' => $this->sekolah->id,
        ])
        ->assertJsonPath('data.created', 1);

    expect(Siswa::where('nis', '9002001')->value('sekolah_id'))->toBe($this->sekolah->id);
});

test('admin can clear cached student import preview', function () {
    Cache::put(ImportSpreadsheetPreviewCache::key($this->admin->id, 'siswa'), ['rows' => []], 7200);

    $this->actingAs($this->admin)
        ->delete(route('admin.manajemen-siswa.impor-ekspor.preview.clear'))
        ->assertOk();

    expect(ImportSpreadsheetPreviewCache::has($this->admin->id, 'siswa'))->toBeFalse();
});

test('student spreadsheet importer service imports rows', function () {
    $file = makeStudentImportFile([
        sampleStudentImportRow('9003001', 'SISWA A'),
        sampleStudentImportRow('9003002', 'SISWA B'),
    ]);
    $rows = StudentSpreadsheetTemplate::parseRows($file->getPathname());

    $result = app(StudentSpreadsheetImporter::class)->import($this->sekolah, $rows);

    expect($result['created'])->toBe(2)
        ->and(Siswa::count())->toBe(2);
});

test('student import does not auto generate guardian phone from nis', function () {
    $rows = [[
        'NIS' => '9001005',
        'NAMA' => 'SISWA TANPA HP',
        'NODAF' => 'NODAF001',
        'UNIT' => 'MA',
        'KELAS' => '12',
        'KELOMPOK' => '01-IBNU HAJAR',
        'ANGKATAN' => '2025/2026',
        'GENDER' => 'L',
        'TEMPAT_LAHIR' => 'Pekanbaru',
        'TANGGAL_LAHIR' => '2010-01-15',
        'ALAMAT' => 'Jl. Contoh',
        'WALI' => 'WALI TEST',
        'STATUS' => 'aktif',
    ]];

    app(StudentSpreadsheetImporter::class)->import($this->sekolah, $rows);

    $siswa = Siswa::with('orangTua')->where('nis', '9001005')->first();

    expect($siswa?->orangTua->first()?->telepon_wali)->toBeNull()
        ->and($siswa?->orangTua->first()?->telepon_ayah)->toBeNull()
        ->and($siswa?->orangTua->first()?->telepon_ibu)->toBeNull()
        ->and($siswa?->orangTua->first()?->email_wali)->toBeNull()
        ->and($siswa?->orangTua->first()?->email_ayah)->toBeNull()
        ->and($siswa?->orangTua->first()?->email_ibu)->toBeNull();
});

test('student import stores guardian phone from nomor hp column', function () {
    $rows = [[
        'NIS' => '9001006',
        'NAMA' => 'SISWA DENGAN HP',
        'NODAF' => 'NODAF001',
        'UNIT' => 'MA',
        'KELAS' => '12',
        'KELOMPOK' => '01-IBNU HAJAR',
        'ANGKATAN' => '2025/2026',
        'GENDER' => 'L',
        'TEMPAT_LAHIR' => 'Pekanbaru',
        'TANGGAL_LAHIR' => '2010-01-15',
        'ALAMAT' => 'Jl. Contoh',
        'WALI' => 'WALI TEST',
        'NOMOR_HP' => '081234567890',
        'STATUS' => 'aktif',
    ]];

    app(StudentSpreadsheetImporter::class)->import($this->sekolah, $rows);

    expect(Siswa::with('orangTua')->where('nis', '9001006')->first()?->orangTua->first()?->telepon_wali)
        ->toBe('6281234567890');
});

test('student preview warns when va suffix collides with existing siswa or within file', function () {
    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->importKelas->id,
        'nis' => '90000001084210041',
        'name' => 'Existing Collision',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $file = makeStudentImportFile([
        sampleStudentImportRow('80000001084210041', 'IMPORT COLLISION A'),
        sampleStudentImportRow('70000001084210041', 'IMPORT COLLISION B'),
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.manajemen-siswa.impor-ekspor.preview'), ['file' => $file])
        ->assertOk();

    $rows = $response->json('data.preview.rows');

    expect($rows[0]['status'])->toBe(1)
        ->and($rows[0]['description'])->toContain('Existing Collision')
        ->and($rows[0]['description'])->toContain('10 digit terakhir NIS');

    expect(
        str_contains($rows[1]['description'], 'baris lain dalam file ini')
        || str_contains($rows[1]['description'], 'beberapa baris dalam file ini')
    )->toBeTrue();
});
