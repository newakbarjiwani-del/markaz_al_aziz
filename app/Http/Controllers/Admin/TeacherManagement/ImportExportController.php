<?php

namespace App\Http\Controllers\Admin\TeacherManagement;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesSpreadsheetImportPreview;
use App\Support\AdminSchoolScope;
use App\Support\AdminSekolahResolver;
use App\Support\TeacherSpreadsheetTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportExportController extends Controller
{
    use DataTableTrait;
    use HandlesSpreadsheetImportPreview;

    protected function importPreviewType(): string
    {
        return 'guru';
    }

    protected function importPreviewPermission(): string
    {
        return 'teachers.create';
    }

    protected function importPreviewTemplateClass(): string
    {
        return TeacherSpreadsheetTemplate::class;
    }

    public function index(): View
    {
        return view('admin.partials.impor-ekspor', array_merge([
            'title' => 'Import/Export Guru',
            'exportDescription' => 'Ekspor data guru (Excel/PDF sesuai filter tabel) tersedia di halaman Data Guru.',
            'exportRoute' => route('admin.manajemen-guru.data-guru.index'),
            'exportButtonLabel' => 'Buka Data Guru',
            'importType' => 'guru',
            'templateRoute' => route('admin.manajemen-guru.impor-ekspor.template'),
            'templateColumns' => TeacherSpreadsheetTemplate::COLUMNS,
            'previewColumns' => ['Baris', 'NIP', 'Nama', 'Jabatan', 'Status', 'Keterangan'],
            'importHint' => 'Unggah file Excel, lalu buat pratinjau sebelum data disimpan ke database.',
            'requiresSchoolSelection' => AdminSekolahResolver::requiresSchoolSelection(auth()->user()),
            'schools' => AdminSekolahResolver::requiresSchoolSelection(auth()->user())
                ? AdminSchoolScope::schools()
                : [],
        ], $this->importPreviewRoutes('admin.manajemen-guru.impor-ekspor')));
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('teachers.view');

        $path = TeacherSpreadsheetTemplate::templatePath();

        abort_unless(is_file($path), 404, 'Template impor guru tidak ditemukan.');

        return response()->download(
            $path,
            'Format Input Guru.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function export(): RedirectResponse
    {
        $this->authorize('teachers.view');

        return redirect()->route('admin.manajemen-guru.data-guru.index');
    }
}
