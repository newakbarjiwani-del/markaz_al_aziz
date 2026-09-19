<?php

namespace App\Http\Controllers\Admin\Perizinan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Perizinan\CheckinPerizinanRequest;
use App\Http\Requests\Perizinan\StorePerizinanRequest;
use App\Http\Requests\Perizinan\UpdatePerizinanRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\JenisPelanggaran;
use App\Models\Perizinan;
use App\Services\Perizinan\PerizinanCheckinService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IzinKeluarMasukController extends Controller
{
    use DataTableTrait;

    protected string $routeGroup = 'portal.perizinan';
    protected string $jenisPerizinan = Perizinan::JENIS_KELUAR_MASUK;
    protected string $title = 'Izin Keluar Masuk Harian';

    public function index(): View
    {
        return view('admin.perizinan.keluar-masuk.index', [
            'title' => '',
            'jenis' => $this->jenisPerizinan,
            'schools' => AdminSchoolScope::schools(),
            'ajaxUrl' => route("{$this->routeGroup}.keluar-masuk.data"),
            'storeUrl' => route("{$this->routeGroup}.keluar-masuk.store"),
            'katalogPelanggaran' => JenisPelanggaran::query()->active()->ordered()->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Perizinan::query()
            ->with(['siswa.kelas', 'sekolah', 'approvedBy', 'createdBy'])
            ->where('jenis_perizinan', $this->jenisPerizinan)
            ->select('perizinan.*');

        AdminSchoolScope::apply($query);

        if ($request->filled('siswa_id')) {
            $query->where('perizinan.siswa_id', $request->integer('siswa_id'));
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->integer('kelas_id')));
        }

        if ($request->filled('sekolah_id')) {
            $query->where('perizinan.sekolah_id', $request->integer('sekolah_id'));
        }

        if ($request->filled('status')) {
            $query->where('perizinan.status', $request->input('status'));
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
            'orderable' => ['alasan', 'pemberi_izin', 'tgl_mulai', 'tgl_sampai', 'status'],
        ];

        return $this->datatableResponse($request, $query, $columns, function (Perizinan $row) {
            $tglMulaiFormatted = $row->tgl_mulai ? $row->tgl_mulai->isoFormat('DD MMM YYYY, HH:mm') : '-';
            $tglSampaiFormatted = $row->tgl_sampai ? $row->tgl_sampai->isoFormat('DD MMM YYYY, HH:mm') : '-';
            $tglKembaliFormatted = $row->tgl_kembali_aktual ? $row->tgl_kembali_aktual->isoFormat('DD MMM YYYY, HH:mm') : '-';

            $canCheckin = in_array($row->status, [Perizinan::STATUS_DISETUJUI, Perizinan::STATUS_TERLAMBAT], true) && ! $row->tgl_kembali_aktual;

            $routePrefix = match ($row->jenis_perizinan) {
                Perizinan::JENIS_KELUAR_MASUK_PONDOK => "{$this->routeGroup}.keluar-masuk-pondok",
                Perizinan::JENIS_PULANG_LIBUR => "{$this->routeGroup}.pulang-libur",
                default => "{$this->routeGroup}.keluar-masuk",
            };

            $actions = [
                'edit' => [
                    'update_url' => route($routePrefix . '.update', $row),
                    'form_target' => 'perizinan-form',
                    'modal_target' => 'perizinan-modal',
                    'record' => array_merge($row->only(['siswa_id', 'sekolah_id', 'alasan', 'penanggung_jawab', 'pemberi_izin', 'status', 'catatan']), [
                        'jenis_perizinan' => $row->jenis_perizinan,
                        'tgl_mulai' => $row->tgl_mulai ? $row->tgl_mulai->format('Y-m-d\TH:i') : '',
                        'tgl_sampai' => $row->tgl_sampai ? $row->tgl_sampai->format('Y-m-d\TH:i') : '',
                        'tgl_kembali_aktual' => $row->tgl_kembali_aktual ? $row->tgl_kembali_aktual->format('Y-m-d\TH:i') : '',
                        'siswa_name' => $row->siswa?->name ?? '',
                        'file_url' => $row->file_url,
                        'file_name' => $row->file_name,
                    ]),
                ],
                'view' => [
                    'url' => route($routePrefix . '.show', $row),
                    'modal_target' => 'perizinan-detail-modal',
                ],
                'delete' => [
                    'url' => route($routePrefix . '.destroy', $row),
                    'confirm_title' => 'Hapus Perizinan',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus data perizinan ini?',
                    'confirm_detail' => [
                        ['label' => 'Siswa', 'value' => $row->siswa?->name ?? '-'],
                        ['label' => 'Jenis', 'value' => $row->jenis_label],
                        ['label' => 'Alasan', 'value' => $row->alasan],
                    ],
                ],
            ];

            if ($canCheckin) {
                $actions['checkin'] = [
                    'url' => route($routePrefix . '.checkin', $row),
                    'siswa_name' => $row->siswa?->name ?? 'Santri',
                ];
            }

            // 1. Siswa / Santri Cell
            $siswaCell = '<div class="font-semibold text-slate-900 dark:text-white">' . e($row->siswa?->name ?? '-') . '</div>'
                . '<div class="text-xs text-slate-500 dark:text-slate-400">NIS ' . e($row->siswa?->nis ?? '-') . ' · ' . e($row->siswa?->kelas?->nama_kelas ?? '-') . '</div>';
            if ($row->file_path) {
                $siswaCell .= '<div class="mt-1"><a href="' . e($row->file_url) . '" target="_blank" class="inline-flex items-center gap-1 text-xs text-primary-600 hover:underline dark:text-primary-400 font-medium"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg><span>' . e($row->file_name) . '</span></a></div>';
            }

            // 2. Alasan Cell
            $alasanCell = '<div class="text-slate-800 dark:text-slate-200">' . e($row->alasan) . '</div>';
            if ($row->penanggung_jawab) {
                $alasanCell .= '<div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><span class="font-medium">PJ:</span> ' . e($row->penanggung_jawab) . '</div>';
            }

            $pemberiIzin = $row->pemberiIzinLabel();
            $pemberiCell = '<div class="font-medium text-slate-800 dark:text-slate-200">' . e($pemberiIzin) . '</div>';

            // 6. Status Cell
            $statusCell = $row->status_badge;
            if ($canCheckin) {
                $statusCell .= '<div class="mt-1.5"><button type="button" class="inline-flex items-center rounded-md bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2" data-action="checkin-perizinan" data-url="' . route($routePrefix . '.checkin', $row) . '" data-siswa-name="' . e($row->siswa?->name ?? 'Santri') . '">Catat Kembali</button></div>';
            }

            return [
                $this->cell($siswaCell, $row->siswa?->name ?? '-', 'html'),
                $this->cell($alasanCell, $row->alasan, 'html'),
                $this->cell($pemberiCell, $pemberiIzin, 'html'),
                $this->cell($tglMulaiFormatted, $row->tgl_mulai?->toIso8601String() ?? '', 'text'),
                $this->cell($tglSampaiFormatted, $row->tgl_sampai?->toIso8601String() ?? '', 'text'),
                $this->cell($tglKembaliFormatted, $row->tgl_kembali_aktual?->toIso8601String() ?? '-', 'text'),
                $this->cell($statusCell, $row->status, 'html'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StorePerizinanRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['file']);

        $data = $this->normalizePemberiIzin($data, $request);

        $data['jenis_perizinan'] = $this->jenisPerizinan;
        $data['created_by'] = $request->user()?->id;

        if (empty($data['status'])) {
            $data['status'] = Perizinan::STATUS_DISETUJUI;
        }

        if ($data['status'] === Perizinan::STATUS_DISETUJUI) {
            $data['approved_by'] = $request->user()?->id;
        }

        if (empty($data['sekolah_id'])) {
            $siswa = \App\Models\Siswa::find($data['siswa_id']);
            $data['sekolah_id'] = $siswa?->sekolah_id;
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . mt_rand(1000, 9999) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $data['file_path'] = $file->storeAs("perizinan/{$this->jenisPerizinan}", $filename, 'public');
        }

        $perizinan = Perizinan::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Perizinan berhasil ditambahkan.',
            'data' => $perizinan,
        ]);
    }

    public function show(Perizinan $keluar_masuk): JsonResponse
    {
        $keluar_masuk->load(['siswa.kelas', 'sekolah', 'approvedBy', 'createdBy']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $keluar_masuk->id,
                'siswa_name' => $keluar_masuk->siswa?->name ?? '-',
                'siswa_nis' => $keluar_masuk->siswa?->nis ?? '-',
                'siswa_kelas' => $keluar_masuk->siswa?->kelas?->nama_kelas ?? '-',
                'sekolah_name' => $keluar_masuk->sekolah?->name ?? '-',
                'jenis_label' => $keluar_masuk->jenis_label,
                'alasan' => $keluar_masuk->alasan,
                'penanggung_jawab' => $keluar_masuk->penanggung_jawab ?? '-',
                'pemberi_izin' => $keluar_masuk->pemberiIzinLabel(),
                'tgl_mulai' => $keluar_masuk->tgl_mulai ? $keluar_masuk->tgl_mulai->isoFormat('D MMMM YYYY, HH:mm') : '-',
                'tgl_sampai' => $keluar_masuk->tgl_sampai ? $keluar_masuk->tgl_sampai->isoFormat('D MMMM YYYY, HH:mm') : '-',
                'tgl_kembali_aktual' => $keluar_masuk->tgl_kembali_aktual ? $keluar_masuk->tgl_kembali_aktual->isoFormat('D MMMM YYYY, HH:mm') : '-',
                'status' => $keluar_masuk->status,
                'status_badge' => $keluar_masuk->status_badge,
                'catatan' => $keluar_masuk->catatan ?? '-',
                'file_url' => $keluar_masuk->file_url,
                'file_name' => $keluar_masuk->file_name,
                'approved_by_name' => $keluar_masuk->pemberiIzinLabel(),
                'created_by_name' => $keluar_masuk->createdBy?->name ?? '-',
            ],
        ]);
    }

    public function update(UpdatePerizinanRequest $request, Perizinan $keluar_masuk): JsonResponse
    {
        $data = $request->validated();
        unset($data['file']);

        $data = $this->normalizePemberiIzin($data, $request);

        if ($data['status'] === Perizinan::STATUS_DISETUJUI && ! $keluar_masuk->approved_by) {
            $data['approved_by'] = $request->user()?->id;
        }

        if ($data['status'] === Perizinan::STATUS_KEMBALI && ! $request->filled('tgl_kembali_aktual')) {
            $data['tgl_kembali_aktual'] = Carbon::now();
        }

        $returnAt = isset($data['tgl_kembali_aktual'])
            ? Carbon::parse($data['tgl_kembali_aktual'])
            : null;
        $recordsReturn = in_array($data['status'], [Perizinan::STATUS_KEMBALI, Perizinan::STATUS_TERLAMBAT], true)
            && ! $keluar_masuk->tgl_kembali_aktual
            && $returnAt !== null;

        $checkinService = app(PerizinanCheckinService::class);
        $isLateReturn = $recordsReturn && $checkinService->isLate($keluar_masuk, $returnAt);

        if ($recordsReturn && $isLateReturn) {
            $result = $checkinService->recordReturn(
                $keluar_masuk,
                $request->user(),
                $returnAt,
                $request->only([
                    'pelanggaran_jenis_pelanggaran_id',
                    'pelanggaran_judul',
                    'pelanggaran_keterangan',
                    'pelanggaran_point',
                    'pelanggaran_tanggal',
                ]),
                $data['catatan'] ?? $keluar_masuk->catatan,
            );

            if ($request->hasFile('file')) {
                $this->replacePerizinanFile($request, $result['perizinan'], $data);
                $result['perizinan']->save();
            }

            $message = 'Data perizinan dan pelanggaran keterlambatan berhasil dicatat.';
            if ($result['pelanggaran']) {
                $message = ActionMessage::withSubject(
                    'Kembali terlambat — pelanggaran dicatat',
                    ActionMessage::siswa($result['perizinan']->siswa)
                );
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result['perizinan'],
            ]);
        }

        if ($request->hasFile('file')) {
            if ($keluar_masuk->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($keluar_masuk->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($keluar_masuk->file_path);
            }

            $file = $request->file('file');
            $jenisFolder = $keluar_masuk->jenis_perizinan ?? $this->jenisPerizinan;
            $filename = time() . '_' . mt_rand(1000, 9999) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $data['file_path'] = $file->storeAs("perizinan/{$jenisFolder}", $filename, 'public');
        }

        $keluar_masuk->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data perizinan berhasil diperbarui.',
            'data' => $keluar_masuk,
        ]);
    }

    public function destroy(Perizinan $keluar_masuk): JsonResponse
    {
        $keluar_masuk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data perizinan berhasil dihapus.',
        ]);
    }

    public function checkinPreview(Request $request, Perizinan $keluar_masuk): JsonResponse
    {
        $returnAt = $request->filled('return_at')
            ? Carbon::parse($request->input('return_at'))
            : null;

        return response()->json([
            'success' => true,
            'data' => app(PerizinanCheckinService::class)->preview($keluar_masuk, $returnAt),
        ]);
    }

    public function checkin(CheckinPerizinanRequest $request, Perizinan $keluar_masuk): JsonResponse
    {
        $returnAt = $request->returnAtFor($keluar_masuk);

        $result = app(PerizinanCheckinService::class)->recordReturn(
            $keluar_masuk,
            $request->user(),
            $returnAt,
            $request->pelanggaranInput(),
            $request->input('catatan'),
        );

        $message = $result['pelanggaran']
            ? ActionMessage::withSubject(
                'Kembali terlambat — pelanggaran dicatat',
                ActionMessage::siswa($result['perizinan']->siswa)
            )
            : 'Konfirmasi kembali santri berhasil dicatat.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result['perizinan'],
            'pelanggaran_id' => $result['pelanggaran']?->id,
        ]);
    }

    protected function replacePerizinanFile(UpdatePerizinanRequest|Request $request, Perizinan $perizinan, array &$data): void
    {
        if ($perizinan->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($perizinan->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($perizinan->file_path);
        }

        $file = $request->file('file');
        $jenisFolder = $perizinan->jenis_perizinan ?? $this->jenisPerizinan;
        $filename = time() . '_' . mt_rand(1000, 9999) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $data['file_path'] = $file->storeAs("perizinan/{$jenisFolder}", $filename, 'public');
        $perizinan->fill($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePemberiIzin(array $data, Request $request): array
    {
        $name = trim((string) ($data['pemberi_izin'] ?? ''));
        $data['pemberi_izin'] = $name !== '' ? $name : $request->user()?->name;

        return $data;
    }
}
