<?php

namespace App\Http\Controllers\Admin\Perizinan;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Perizinan;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapLaporanController extends Controller
{
    use DataTableTrait;

    protected string $routeGroup = 'portal.perizinan';

    public function index(): View
    {
        return view('admin.perizinan.rekap-laporan', [
            'title' => 'Rekap & Laporan Perizinan',
            'schools' => AdminSchoolScope::schools(),
            'ajaxUrl' => route("{$this->routeGroup}.rekap-laporan.data"),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Perizinan::query()
            ->with(['siswa.kelas', 'sekolah', 'approvedBy', 'createdBy'])
            ->select('perizinan.*');

        AdminSchoolScope::apply($query);

        if ($request->filled('jenis_perizinan')) {
            $query->where('perizinan.jenis_perizinan', $request->input('jenis_perizinan'));
        }

        if ($request->filled('status')) {
            $query->where('perizinan.status', $request->input('status'));
        }

        if ($request->filled('siswa_id')) {
            $query->where('perizinan.siswa_id', $request->integer('siswa_id'));
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->integer('kelas_id')));
        }

        if ($request->filled('sekolah_id')) {
            $query->where('perizinan.sekolah_id', $request->integer('sekolah_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('tgl_mulai', '>=', $request->input('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('tgl_mulai', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $query->matchingSiswa($request->input('q'));

        $columns = [
            'searchable' => ['siswa.name', 'siswa.nis', 'alasan', 'penanggung_jawab', 'pemberi_izin'],
            'orderable' => ['jenis_perizinan', 'alasan', 'tgl_mulai', 'tgl_sampai', 'status'],
        ];

        return $this->datatableResponse($request, $query, $columns, function (Perizinan $row) {
            $tglMulaiFormatted = $row->tgl_mulai ? $row->tgl_mulai->isoFormat('DD MMM YYYY, HH:mm') : '-';
            $tglSampaiFormatted = $row->tgl_sampai ? $row->tgl_sampai->isoFormat('DD MMM YYYY, HH:mm') : '-';
            $tglKembaliFormatted = $row->tgl_kembali_aktual ? $row->tgl_kembali_aktual->isoFormat('DD MMM YYYY, HH:mm') : '-';

            $routePrefix = match ($row->jenis_perizinan) {
                Perizinan::JENIS_KELUAR_MASUK_PONDOK => "{$this->routeGroup}.keluar-masuk-pondok",
                Perizinan::JENIS_PULANG_LIBUR => "{$this->routeGroup}.pulang-libur",
                default => "{$this->routeGroup}.keluar-masuk",
            };

            $actions = [
                'view' => [
                    'url' => route($routePrefix . '.show', $row),
                    'modal_target' => 'perizinan-detail-modal',
                ],
            ];

            // 0. Jenis Perizinan Badge
            $jenisBadge = '<span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">' . e($row->jenis_label) . '</span>';

            // 1. Siswa / Santri Cell
            $siswaCell = '<div class="font-semibold text-slate-900 dark:text-white">' . e($row->siswa?->name ?? '-') . '</div>'
                . '<div class="text-xs text-slate-500 dark:text-slate-400">NIS ' . e($row->siswa?->nis ?? '-') . ' · ' . e($row->siswa?->kelas?->nama_kelas ?? '-') . ' (' . e($row->sekolah?->name ?? '-') . ')</div>';
            if ($row->file_path) {
                $siswaCell .= '<div class="mt-1"><a href="' . e($row->file_url) . '" target="_blank" class="inline-flex items-center gap-1 text-xs text-primary-600 hover:underline dark:text-primary-400 font-medium"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg><span>' . e($row->file_name) . '</span></a></div>';
            }

            // 2. Alasan Cell
            $alasanCell = '<div class="text-slate-800 dark:text-slate-200">' . e($row->alasan) . '</div>';
            if ($row->penanggung_jawab) {
                $alasanCell .= '<div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><span class="font-medium">PJ:</span> ' . e($row->penanggung_jawab) . '</div>';
            }

            $pemberiIzin = $row->pemberiIzinLabel();
            $pemberiCell = '<div class="font-medium text-primary-700 dark:text-primary-300">' . e($pemberiIzin) . '</div>';

            return [
                $this->cell($jenisBadge, $row->jenis_label, 'html'),
                $this->cell($siswaCell, $row->siswa?->name ?? '-', 'html'),
                $this->cell($alasanCell, $row->alasan, 'html'),
                $this->cell($pemberiCell, $pemberiIzin, 'html'),
                $this->cell($tglMulaiFormatted, $row->tgl_mulai?->toIso8601String() ?? '', 'text'),
                $this->cell($tglSampaiFormatted, $row->tgl_sampai?->toIso8601String() ?? '', 'text'),
                $this->cell($tglKembaliFormatted, $row->tgl_kembali_aktual?->toIso8601String() ?? '-', 'text'),
                $this->cell($row->status_badge, $row->status, 'html'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }
}
