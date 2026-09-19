<?php

use App\Models\Guru;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\TeacherSpreadsheetImporter;
use App\Support\ImportSpreadsheetPreviewCache;
use App\Support\ImportStoreMethod;
use App\Support\TeacherSpreadsheetTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
        'username' => 'admin.guru.import',
        'name' => 'Admin Guru Import',
        'email' => 'admin-guru-import@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

function makeTeacherImportFile(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([TeacherSpreadsheetTemplate::COLUMNS, ...$rows]);

    $path = tempnam(sys_get_temp_dir(), 'guru-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'format-input-guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('admin can download teacher import template', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-guru.impor-ekspor.template'))
        ->assertOk()
        ->assertDownload('Format Input Guru.xlsx');
});

test('admin can preview teacher import with invalid status rows', function () {
    $file = makeTeacherImportFile([
        ['GR-MA-101', 'AHMAD IMPORT, S.Pd', 'Guru Mata Pelajaran', 'Guru Tetap', 'IV/a', '6281312345678', 'aktif'],
        ['', 'INVALID GURU', 'Guru Mapel', 'PNS', 'IV/a', '6281312345678', 'aktif'],
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-guru.impor-ekspor.preview'), ['file' => $file])
        ->assertJsonPath('data.preview.summary.will_create', 1)
        ->assertJsonPath('data.preview.summary.invalid', 1)
        ->assertJsonPath('data.preview.rows.1.status', 0);
});

test('admin can import teachers from cached preview', function () {
    $file = makeTeacherImportFile([
        ['GR-MA-101', 'AHMAD IMPORT, S.Pd', 'Guru Mata Pelajaran', 'Guru Tetap', 'IV/a', '6281312345678', 'aktif'],
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-guru.impor-ekspor.preview'), ['file' => $file]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-guru.impor-ekspor.confirm'), [
            'method' => ImportStoreMethod::CREATE_AND_UPDATE,
        ])
        ->assertJsonPath('data.created', 1);

    $guru = Guru::where('nip', 'GR-MA-101')->first();

    expect($guru)->not->toBeNull()
        ->and($guru->name)->toBe('AHMAD IMPORT, S.Pd')
        ->and($guru->profil)->not->toBeNull();
});

test('import updates existing teacher by nip after preview confirm', function () {
    Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'GR-MA-102',
        'name' => 'Nama Lama',
        'jabatan' => 'Guru Mapel',
        'status' => 'aktif',
    ]);

    $file = makeTeacherImportFile([
        ['GR-MA-102', 'NAMA BARU', 'Wali Kelas', 'Honorer', 'III/b', '6281399999999', 'nonaktif'],
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-guru.impor-ekspor.preview'), ['file' => $file])
        ->assertJsonPath('data.preview.rows.0.status', 2);

    $this->actingAs($this->admin)
        ->post(route('admin.manajemen-guru.impor-ekspor.confirm'), [
            'method' => ImportStoreMethod::UPDATE_ONLY,
        ])
        ->assertJsonPath('data.updated', 1);

    expect(Guru::where('nip', 'GR-MA-102')->value('name'))->toBe('NAMA BARU');
});

test('super admin can import teachers when sekolah is selected', function () {
    $superAdmin = User::create([
        'username' => 'superadmin.guru.import',
        'name' => 'Super Admin Guru Import',
        'email' => 'superadmin-guru-import@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $superAdmin->assignRole('super_admin');

    $file = makeTeacherImportFile([
        ['GR-MA-301', 'GURU SUPERADMIN', 'Guru Mapel', 'PNS', 'IV/a', '6281200000301', 'aktif'],
    ]);

    $this->actingAs($superAdmin)
        ->post(route('admin.manajemen-guru.impor-ekspor.preview'), [
            'file' => $file,
            'sekolah_id' => $this->sekolah->id,
        ]);

    $this->actingAs($superAdmin)
        ->post(route('admin.manajemen-guru.impor-ekspor.confirm'), [
            'method' => ImportStoreMethod::CREATE_AND_UPDATE,
            'sekolah_id' => $this->sekolah->id,
        ])
        ->assertJsonPath('data.created', 1);

    expect(Guru::where('nip', 'GR-MA-301')->value('sekolah_id'))->toBe($this->sekolah->id);
});

test('admin can clear cached teacher import preview', function () {
    ImportSpreadsheetPreviewCache::put($this->admin->id, 'guru', ['rows' => []]);

    $this->actingAs($this->admin)
        ->delete(route('admin.manajemen-guru.impor-ekspor.preview.clear'))
        ->assertOk();

    expect(ImportSpreadsheetPreviewCache::has($this->admin->id, 'guru'))->toBeFalse();
});

test('teacher spreadsheet importer service imports rows', function () {
    $rows = [
        [
            'NIP' => 'GR-MA-201',
            'NAMA' => 'GURU SATU',
            'JABATAN' => 'Guru Mapel',
            'JENIS_GURU' => 'PNS',
            'GOLONGAN' => 'IV/a',
            'TELEPON' => '6281200000001',
            'STATUS' => 'aktif',
        ],
        [
            'NIP' => 'GR-MA-202',
            'NAMA' => 'GURU DUA',
            'JABATAN' => 'Wali Kelas',
            'JENIS_GURU' => 'Honorer',
            'GOLONGAN' => '',
            'TELEPON' => '',
            'STATUS' => 'aktif',
        ],
    ];

    $result = app(TeacherSpreadsheetImporter::class)->import($this->sekolah, $rows);

    expect($result['created'])->toBe(2)
        ->and(Guru::count())->toBe(2);
});
