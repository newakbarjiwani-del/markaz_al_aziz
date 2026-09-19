<?php

namespace App\Http\Controllers\Admin\AchievementViolation;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesSpreadsheetImportPreview;
use App\Support\AdminSchoolScope;
use App\Support\AdminSekolahResolver;
use App\Support\SiswaCatatanSpreadsheetTemplate;
use App\Support\VirtualAccountNumber;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrestasiImportExportController extends Controller
{
    use DataTableTrait;
    use HandlesSpreadsheetImportPreview;

    protected function importPreviewType(): string
    {
        return 'prestasi';
    }

    protected function importPreviewPermission(): string
    {
        return 'prestasi-siswa.create';
    }

    protected function importPreviewTemplateClass(): string
    {
        return SiswaCatatanSpreadsheetTemplate::class;
    }

    public function index(): View
    {
        return view('admin.partials.impor-ekspor', array_merge([
            'title' => 'Import Prestasi Siswa',
            'exportDescription' => 'Ekspor data prestasi siswa (Excel/PDF sesuai filter tabel) tersedia di halaman Prestasi Siswa.',
            'exportRoute' => route('admin.prestasi-pelanggaran.prestasi-siswa.index'),
            'exportButtonLabel' => 'Buka Prestasi Siswa',
            'importType' => 'prestasi',
            'templateRoute' => route('admin.prestasi-pelanggaran.impor-prestasi.template', [
                'v' => SiswaCatatanSpreadsheetTemplate::TEMPLATE_VERSION,
            ]),
            'templateColumns' => SiswaCatatanSpreadsheetTemplate::COLUMNS,
            'previewColumns' => ['Baris', 'NIS', 'Nama', 'Kelas', 'Judul', 'Tanggal', 'Point', 'Status', 'Keterangan'],
            'importHint' => 'Unggah file Excel, lalu buat pratinjau sebelum data disimpan. Kolom wajib: NIS (angka maks. '.VirtualAccountNumber::NIS_MAX_LENGTH.' digit, format teks di Excel), JUDUL, TANGGAL.',
            'requiresSchoolSelection' => AdminSekolahResolver::requiresSchoolSelection(auth()->user()),
            'schools' => AdminSekolahResolver::requiresSchoolSelection(auth()->user())
                ? AdminSchoolScope::schools()
                : [],
        ], $this->importPreviewRoutes('admin.prestasi-pelanggaran.impor-prestasi')));
    }

    public function template(): StreamedResponse
    {
        $this->authorize('prestasi-siswa.view');

        return SiswaCatatanSpreadsheetTemplate::downloadResponse('prestasi');
    }
}
