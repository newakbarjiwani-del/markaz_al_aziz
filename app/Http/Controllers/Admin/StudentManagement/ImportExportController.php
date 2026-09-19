<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesSpreadsheetImportPreview;
use App\Support\AdminSchoolScope;
use App\Support\AdminSekolahResolver;
use App\Support\StudentSpreadsheetTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    use DataTableTrait;
    use HandlesSpreadsheetImportPreview;

    protected function importPreviewType(): string
    {
        return 'siswa';
    }

    protected function importPreviewPermission(): string
    {
        return 'students.create';
    }

    protected function importPreviewTemplateClass(): string
    {
        return StudentSpreadsheetTemplate::class;
    }

    public function index(): View
    {
        return view('admin.partials.impor-ekspor', array_merge([
            'title' => 'Import/Export Siswa',
            'exportDescription' => 'Ekspor data siswa (Excel/PDF sesuai filter tabel) tersedia di halaman Data Siswa.',
            'exportRoute' => route('admin.manajemen-siswa.data-siswa.index'),
            'exportButtonLabel' => 'Buka Data Siswa',
            'importType' => 'siswa',
            'templateRoute' => route('admin.manajemen-siswa.impor-ekspor.template', [
                'v' => StudentSpreadsheetTemplate::TEMPLATE_VERSION,
            ]),
            'templateColumns' => StudentSpreadsheetTemplate::COLUMNS,
            'previewColumns' => ['Baris', 'NIS', 'Nama', 'Kelas', 'Tanggal Lahir', 'Status', 'Keterangan'],
            'siswaImportReview' => true,
            'importHint' => 'Unggah file Excel, lalu buat pratinjau sebelum data disimpan. Kolom NIS: angka maks. '.\App\Support\VirtualAccountNumber::NIS_MAX_LENGTH.' digit (format teks di Excel agar NIS panjang tidak rusak).',
            'requiresSchoolSelection' => AdminSekolahResolver::requiresSchoolSelection(auth()->user()),
            'schools' => AdminSekolahResolver::requiresSchoolSelection(auth()->user())
                ? AdminSchoolScope::schools()
                : [],
        ], $this->importPreviewRoutes('admin.manajemen-siswa.impor-ekspor')));
    }

    public function template(): StreamedResponse
    {
        $this->authorize('students.view');

        return StudentSpreadsheetTemplate::downloadResponse();
    }

    public function export(): RedirectResponse
    {
        $this->authorize('students.view');

        return redirect()->route('admin.manajemen-siswa.data-siswa.index');
    }
}
