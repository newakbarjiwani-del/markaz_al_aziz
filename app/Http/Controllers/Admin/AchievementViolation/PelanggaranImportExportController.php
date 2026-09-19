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

class PelanggaranImportExportController extends Controller
{
    use DataTableTrait;
    use HandlesSpreadsheetImportPreview;

    protected function importPreviewType(): string
    {
        return 'pelanggaran';
    }

    protected function importPreviewPermission(): string
    {
        return 'pelanggaran-siswa.create';
    }

    protected function importPreviewTemplateClass(): string
    {
        return SiswaCatatanSpreadsheetTemplate::class;
    }

    public function index(): View
    {
        return view('admin.partials.impor-ekspor', array_merge([
            'title' => 'Import Pelanggaran Siswa',
            'exportDescription' => 'Ekspor data pelanggaran siswa (Excel/PDF sesuai filter tabel) tersedia di halaman Pelanggaran Siswa.',
            'exportRoute' => route('admin.prestasi-pelanggaran.pelanggaran-siswa.index'),
            'exportButtonLabel' => 'Buka Pelanggaran Siswa',
            'importType' => 'pelanggaran',
            'templateRoute' => route('admin.prestasi-pelanggaran.impor-pelanggaran.template', [
                'v' => SiswaCatatanSpreadsheetTemplate::TEMPLATE_VERSION,
            ]),
            'templateColumns' => SiswaCatatanSpreadsheetTemplate::PELANGGARAN_COLUMNS,
            'previewColumns' => ['Baris', 'NIS', 'Nama', 'Kelas', 'Judul', 'Tanggal', 'Point', 'Status', 'Keterangan'],
            'importHint' => 'Unggah file Excel, lalu buat pratinjau sebelum data disimpan. Kolom wajib: NIS, TANGGAL, dan JUDUL atau JENIS_PELANGGARAN (katalog). POINT opsional jika memakai katalog.',
            'requiresSchoolSelection' => AdminSekolahResolver::requiresSchoolSelection(auth()->user()),
            'schools' => AdminSekolahResolver::requiresSchoolSelection(auth()->user())
                ? AdminSchoolScope::schools()
                : [],
        ], $this->importPreviewRoutes('admin.prestasi-pelanggaran.impor-pelanggaran')));
    }

    public function template(): StreamedResponse
    {
        $this->authorize('pelanggaran-siswa.view');

        return SiswaCatatanSpreadsheetTemplate::downloadResponse('pelanggaran');
    }
}
