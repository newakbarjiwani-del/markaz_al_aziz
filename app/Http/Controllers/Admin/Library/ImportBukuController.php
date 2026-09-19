<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesSpreadsheetImportPreview;
use App\Support\AdminSchoolScope;
use App\Support\AdminSekolahResolver;
use App\Support\BukuSpreadsheetTemplate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportBukuController extends Controller
{
    use DataTableTrait;
    use HandlesSpreadsheetImportPreview;

    protected function importPreviewType(): string
    {
        return 'buku';
    }

    protected function importPreviewPermission(): string
    {
        return 'library.create';
    }

    protected function importPreviewTemplateClass(): string
    {
        return BukuSpreadsheetTemplate::class;
    }

    protected function importPreviewRequiresSekolah(): bool
    {
        return false;
    }

    public function index(): View
    {
        return view('admin.partials.impor-ekspor', array_merge($this->viewData(), $this->importPreviewRoutes($this->routePrefix())));
    }

    public function template(): StreamedResponse
    {
        $this->authorize('library.view');

        return BukuSpreadsheetTemplate::downloadResponse();
    }

    protected function routePrefix(): string
    {
        return 'admin.perpustakaan.impor-buku';
    }

    /** @return array<string, mixed> */
    protected function viewData(): array
    {
        return [
            'title' => 'Import Buku',
            'exportDescription' => 'Ekspor katalog buku (Excel/PDF sesuai filter) tersedia di halaman Katalog Buku.',
            'exportRoute' => route('admin.perpustakaan.katalog-buku.index'),
            'exportButtonLabel' => 'Buka Katalog Buku',
            'importType' => 'buku',
            'templateRoute' => route($this->routePrefix().'.template', ['v' => BukuSpreadsheetTemplate::TEMPLATE_VERSION]),
            'templateColumns' => BukuSpreadsheetTemplate::COLUMNS,
            'previewColumns' => ['Baris', 'Kode', 'ISBN', 'Judul', 'Pengarang', 'Penerbit', 'Jumlah', 'Status', 'Keterangan'],
            'importHint' => 'Unggah file Excel sesuai template Kemenag. Kolom wajib: JUDUL dan JUMLAH. ISBN opsional (unik global, kunci pembaruan bila diisi). Sekolah opsional — kosong = katalog lintas sekolah.',
            'requiresSchoolSelection' => false,
            'schoolOptional' => true,
            'schools' => AdminSekolahResolver::requiresSchoolSelection(auth()->user())
                ? AdminSchoolScope::schools()
                : [],
        ];
    }
}
