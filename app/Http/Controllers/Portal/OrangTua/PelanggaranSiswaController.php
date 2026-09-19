<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\PelanggaranSiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PelanggaranSiswaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use PortalAccess;

    public function index(): View
    {
        $children = $this->ortuChildren();

        return view('portal.ortu.pelanggaran-siswa', [
            'title' => 'Pelanggaran Siswa',
            'children' => $children,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PelanggaranSiswa::query()
            ->with(['siswa.kelas', 'reportedBy', 'buktiCatatan'])
            ->select('pelanggaran_siswa.*');

        $this->applyOrtuSiswaScope($query, $request);

        if ($request->filled('date_from')) {
            $query->where('tanggal', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('tanggal', '<=', $request->input('date_to'));
        }

        $columns = [
            'searchable' => ['judul', 'keterangan', 'siswa.name', 'siswa.nis'],
            'orderable' => ['judul', 'tanggal', 'point', 'judul'],
        ];

        return $this->datatableResponse($request, $query, $columns, function (PelanggaranSiswa $row) {
            $buktiCount = $row->buktiCatatan()->count();

            return [
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $this->cell($row->judul, $row->judul),
                $this->dateCell($row->tanggal),
                $row->point,
                $buktiCount > 0
                    ? $this->cell("<span class='badge bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'>{$buktiCount} file</span>", $buktiCount, 'html')
                    : '-',
                $this->cell(
                    '<button type="button" class="btn-action btn-action--icon pelanggaran-detail-btn" title="Detail">'
                    .'<i class="ti ti-eye"></i></button>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function show(PelanggaranSiswa $pelanggaranSiswa): JsonResponse
    {
        abort_unless(
            in_array($pelanggaranSiswa->siswa_id, $this->ortuChildIds(), true),
            403,
            'Data pelanggaran tidak termasuk anak yang terhubung.'
        );

        $pelanggaranSiswa->load(['siswa.kelas', 'reportedBy', 'buktiCatatan', 'jenisPelanggaran']);

        return $this->jsonSuccess('OK', [
            'id' => $pelanggaranSiswa->id,
            'siswa_name' => $pelanggaranSiswa->siswa?->name,
            'siswa_nis' => $pelanggaranSiswa->siswa?->nis,
            'siswa_kelas' => $pelanggaranSiswa->siswa?->kelas?->name,
            'judul' => $pelanggaranSiswa->judul,
            'keterangan' => $pelanggaranSiswa->keterangan,
            'tanggal' => $pelanggaranSiswa->tanggal->format('Y-m-d'),
            'point' => $pelanggaranSiswa->point,
            'jenis_pelanggaran_id' => $pelanggaranSiswa->jenis_pelanggaran_id,
            'jenis_nama' => $pelanggaranSiswa->jenisPelanggaran?->nama,
            'jenis_level' => $pelanggaranSiswa->jenisPelanggaran?->levelLabel(),
            'jenis_sanction' => $pelanggaranSiswa->jenisPelanggaran?->sanctionLabel(),
            'jenis_point' => $pelanggaranSiswa->jenisPelanggaran?->point,
            'reported_by_name' => $pelanggaranSiswa->reportedBy?->name,
            'bukti' => $pelanggaranSiswa->buktiCatatan->map(fn ($b) => [
                'id' => $b->id,
                'url' => $b->url,
                'original_name' => $b->original_name,
                'is_image' => $b->isImage(),
                'is_pdf' => $b->isPdf(),
            ]),
        ]);
    }
}
