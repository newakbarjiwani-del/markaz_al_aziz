<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Import\ImportSpreadsheetRequest;
use App\Http\Traits\DataTableTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ExportAbsensiController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.partials.impor-ekspor', [
            'title' => 'Export Absensi',
            'exportDescription' => 'Unduh laporan absensi siswa dan guru per periode.',
            'importRoute' => route('admin.absensi.ekspor-absensi.import'),
            'templateColumns' => ['Tanggal', 'NIS/NIP', 'Nama', 'Kelas/Jabatan', 'Status', 'Jam Masuk'],
        ]);
    }

    public function import(ImportSpreadsheetRequest $request): JsonResponse
    {
        $this->authorize('attendance.create');

        return $this->jsonSuccess('File absensi berhasil diunggah untuk diproses.');
    }
}
