<?php

namespace App\Http\Controllers\Portal\Siswa;

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
        $siswa = $this->linkedSiswa();

        return view('portal.siswa.pelanggaran-siswa', [
            'title' => 'Pelanggaran Saya',
            'siswa' => $siswa,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $siswa = $this->linkedSiswa();

        $query = PelanggaranSiswa::query()
            ->with(['siswa.kelas', 'reportedBy', 'buktiCatatan'])
            ->where('siswa_id', $siswa->id)
            ->select('pelanggaran_siswa.*');

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

        return $this->datatableResponse($request, $query, $columns, function (PelanggaranSiswa $row) {
            $buktiCount = $row->buktiCatatan()->count();

            return [
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
        $siswa = $this->linkedSiswa();

        abort_unless(
            (int) $pelanggaranSiswa->siswa_id === (int) $siswa->id,
            403,
            'Data pelanggaran bukan milik akun siswa ini.'
        );

        $pelanggaranSiswa->load(['siswa.kelas', 'reportedBy', 'buktiCatatan', 'jenisPelanggaran']);

        return $this->jsonSuccess('OK', [
            'id' => $pelanggaranSiswa->id,
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
