<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\HandlesSpreadsheetImportPreview;
use App\Support\AdminSchoolScope;
use App\Support\AdminSekolahResolver;
use App\Support\TagihanSpreadsheetTemplate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    use DataTableTrait;
    use HandlesSpreadsheetImportPreview;

    protected function importPreviewType(): string
    {
        return 'tagihan';
    }

    protected function importPreviewPermission(): string
    {
        return 'finance.create';
    }

    protected function importPreviewTemplateClass(): string
    {
        return TagihanSpreadsheetTemplate::class;
    }

    public function index(): View
    {
        return view('admin.partials.impor-ekspor', array_merge([
            'title' => 'Import Tagihan',
            'exportDescription' => 'Ekspor tagihan belum tersedia.',
            'importType' => 'tagihan',
            'templateRoute' => route('admin.keuangan.impor-tagihan.template', [
                'v' => TagihanSpreadsheetTemplate::TEMPLATE_VERSION,
            ]),
            'templateColumns' => TagihanSpreadsheetTemplate::COLUMNS,
            'previewColumns' => ['Baris', 'NIS', 'Nama', 'Jenis', 'Periode', 'Tahun Akademik', 'Nominal', 'Status', 'Keterangan'],
            'importHint' => 'Unggah file Excel sesuai template. Kolom wajib: NIS (angka maks. '.\App\Support\VirtualAccountNumber::NIS_MAX_LENGTH.' digit, format teks), TAGIHAN (nominal), JENIS_TAGIHAN, PERIODE, TAHUN_AKADEMIK.',
            'requiresSchoolSelection' => AdminSekolahResolver::requiresSchoolSelection(auth()->user()),
            'schools' => AdminSekolahResolver::requiresSchoolSelection(auth()->user())
                ? AdminSchoolScope::schools()
                : [],
        ], $this->importPreviewRoutes('admin.keuangan.impor-tagihan')));
    }

    public function template(): StreamedResponse
    {
        $this->authorize('finance.view');

        return TagihanSpreadsheetTemplate::downloadResponse();
    }
}
