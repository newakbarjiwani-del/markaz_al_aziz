<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\PrestasiSiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrestasiSiswaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use PortalAccess;

    public function index(): View
    {
        $siswa = $this->linkedSiswa();

        return view('portal.siswa.prestasi-siswa', [
            'title' => 'Prestasi Saya',
            'siswa' => $siswa,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $siswa = $this->linkedSiswa();

        $query = PrestasiSiswa::query()
            ->with(['siswa.kelas', 'reportedBy', 'buktiCatatan'])
            ->where('siswa_id', $siswa->id)
            ->select('prestasi_siswa.*');

        if ($request->filled('date_from')) {
            $query->where('tanggal', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('tanggal', '<=', $request->input('date_to'));
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $columns = [
            'searchable' => ['judul', 'keterangan'],
            'orderable' => ['judul', 'tanggal', 'point', 'judul'],
        ];

        return $this->datatableResponse($request, $query, $columns, function (PrestasiSiswa $row) {
            $buktiCount = $row->buktiCatatan()->count();

            return [
                $this->cell($row->judul, $row->judul),
                $this->dateCell($row->tanggal),
                $row->point,
                $buktiCount > 0
                    ? $this->cell("<span class='badge bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'>{$buktiCount} file</span>", $buktiCount, 'html')
                    : '-',
                $this->cell(
                    '<button type="button" class="btn-action btn-action--icon prestasi-detail-btn" title="Detail">'
                    .'<i class="ti ti-eye"></i></button>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function show(PrestasiSiswa $prestasiSiswa): JsonResponse
    {
        $siswa = $this->linkedSiswa();

        abort_unless(
            (int) $prestasiSiswa->siswa_id === (int) $siswa->id,
            403,
            'Data prestasi bukan milik akun siswa ini.'
        );

        $prestasiSiswa->load(['siswa.kelas', 'reportedBy', 'buktiCatatan']);

        return $this->jsonSuccess('OK', [
            'id' => $prestasiSiswa->id,
            'judul' => $prestasiSiswa->judul,
            'keterangan' => $prestasiSiswa->keterangan,
            'tanggal' => $prestasiSiswa->tanggal->format('Y-m-d'),
            'point' => $prestasiSiswa->point,
            'reported_by_name' => $prestasiSiswa->reportedBy?->name,
            'bukti' => $prestasiSiswa->buktiCatatan->map(fn ($b) => [
                'id' => $b->id,
                'url' => $b->url,
                'original_name' => $b->original_name,
                'is_image' => $b->isImage(),
                'is_pdf' => $b->isPdf(),
            ]),
        ]);
    }
}
