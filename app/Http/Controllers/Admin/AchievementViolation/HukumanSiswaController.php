<?php

namespace App\Http\Controllers\Admin\AchievementViolation;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StoreHukumanSiswaRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\BuktiCatatan;
use App\Models\HukumanSiswa;
use App\Models\PelanggaranSiswa;
use App\Models\Siswa;
use App\Services\HukumanRecommendationService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\BuktiCatatanRules;
use App\Support\HukumanStatus;
use App\Support\ImageConverter;
use App\Support\PelanggaranSanction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HukumanSiswaController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private readonly HukumanRecommendationService $recommendationService,
    ) {}

    public function index(): View
    {
        return view('admin.prestasi-pelanggaran.hukuman-siswa', [
            'title' => 'Hukuman Siswa',
            'schools' => AdminSchoolScope::schools(),
            'sanctionOptions' => PelanggaranSanction::labels(),
            'statusOptions' => HukumanStatus::labels(),
            'prefillSiswaId' => request()->integer('siswa_id') ?: null,
            'openCreate' => request('open') === 'create',
            'hukumanMinPoints' => $this->recommendationService->minPoints(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('hukuman-siswa.view');

        $query = HukumanSiswa::query()
            ->with(['siswa.kelas', 'processedBy'])
            ->withCount('buktiCatatan')
            ->select('hukuman_siswa.*');

        AdminSchoolScope::apply($query);

        if ($request->filled('siswa_id')) {
            $query->where('siswa_id', $request->integer('siswa_id'));
        }

        if ($request->filled('status') && HukumanStatus::normalize($request->input('status')) !== null) {
            $query->where('status', HukumanStatus::normalize($request->input('status')));
        }

        if (! $request->has('order.0.column')) {
            $query->orderByDesc('total_point')->orderByDesc('tanggal');
        }

        $columns = [
            'searchable' => ['siswa.name', 'siswa.nis', 'sanction', 'keterangan'],
            'orderable' => [null, null, null, 'total_point', null, null, null, 'tanggal', null],
        ];

        return $this->datatableResponse($request, $query, $columns, function (HukumanSiswa $row) {
            $statusClass = match ((int) $row->status) {
                HukumanStatus::SELESAI => 'badge-green',
                HukumanStatus::DIBATALKAN => 'badge-gray',
                HukumanStatus::MENUNGGU => 'badge-yellow',
                default => 'badge-blue',
            };

            $buktiCount = (int) $row->bukti_catatan_count;

            $actions = [
                'view' => [
                    'url' => route('admin.prestasi-pelanggaran.hukuman-siswa.show', $row),
                    'modal_target' => 'hukuman-siswa-detail-modal',
                ],
                'edit' => [
                    'update_url' => route('admin.prestasi-pelanggaran.hukuman-siswa.update', $row),
                    'form_target' => 'hukuman-siswa-form',
                    'modal_target' => 'hukuman-siswa-modal',
                    'record' => array_merge($row->only(['siswa_id', 'sanction', 'status', 'keterangan', 'total_point', 'recommended_sanction']), [
                        'tanggal' => $row->tanggal->format('Y-m-d'),
                        'show_url' => route('admin.prestasi-pelanggaran.hukuman-siswa.show', $row),
                    ]),
                ],
                'delete' => [
                    'url' => route('admin.prestasi-pelanggaran.hukuman-siswa.destroy', $row),
                    'confirm_title' => 'Hapus Hukuman',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus catatan hukuman ini?',
                    'confirm_detail' => [
                        ['label' => 'Siswa', 'value' => $row->siswa?->name ?? '-'],
                        ['label' => 'Hukuman', 'value' => $row->sanctionLabel()],
                    ],
                ],
            ];

            return [
                $row->siswa?->name ?? '-',
                $row->siswa?->nis ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                (int) $row->total_point,
                $row->recommendedSanctionLabel(),
                $row->sanctionLabel(),
                $this->badgeCell($row->statusLabel(), $statusClass),
                $buktiCount > 0
                    ? $this->cell("<span class='badge bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'>{$buktiCount} file</span>", $buktiCount, 'html')
                    : '-',
                $this->dateCell($row->tanggal),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function recommend(Siswa $siswa): JsonResponse
    {
        $this->authorize('hukuman-siswa.create');

        $siswa->load('kelas:id,name');
        $payload = $this->recommendationService->recommendForSiswa($siswa);

        if (! $payload['eligible']) {
            return $this->jsonError(
                'Siswa belum memenuhi syarat hukuman (minimal '.$payload['min_points'].' poin pelanggaran).',
                $payload
            );
        }

        return $this->jsonSuccess('OK', $payload);
    }

    public function eligibleData(Request $request): JsonResponse
    {
        $this->authorize('hukuman-siswa.view');

        $min = $this->recommendationService->minPoints();
        $query = $this->recommendationService->eligibleSiswaQuery()
            ->with('kelas:id,name');

        if ($request->filled('kelas_id')) {
            $query->where('siswa.kelas_id', $request->integer('kelas_id'));
        }

        if ($request->filled('sekolah_id')) {
            $query->where('siswa.sekolah_id', $request->integer('sekolah_id'));
        }

        if (! $request->has('order.0.column')) {
            $query->orderByDesc(DB::raw('COALESCE(SUM(pelanggaran_siswa.point), 0)'));
        }

        $columns = [
            'searchable' => ['siswa.name', 'siswa.nis'],
            'orderable' => ['siswa.name', 'siswa.nis', null, DB::raw('COALESCE(SUM(pelanggaran_siswa.point), 0)'), null],
        ];

        return $this->datatableResponse($request, $query, $columns, function ($row) use ($min) {
            $actionHtml = '-';
            if (auth()->user()?->can('hukuman-siswa.create')) {
                $actionHtml = '<button type="button" class="btn-primary text-xs"'
                    .' data-hukuman-eligible-siswa="'.e((string) $row->id).'"'
                    .' data-siswa-name="'.e($row->name ?? '').'"'
                    .' data-siswa-nis="'.e($row->nis ?? '').'"'
                    .' data-total-point="'.e((string) $row->total_point).'">'
                    .'<i class="ti ti-gavel mr-1"></i>Terbitkan</button>';
            }

            return [
                $row->name ?? '-',
                $row->nis ?? '-',
                $row->kelas?->name ?? '-',
                (int) $row->total_point,
                $this->cell(
                    '<span class="text-xs text-slate-500">Min. '.$min.'</span>',
                    $min,
                    'html'
                ),
                $this->cell($actionHtml, null, 'html'),
            ];
        });
    }

    public function store(StoreHukumanSiswaRequest $request): JsonResponse
    {
        $siswa = Siswa::findOrFail($request->integer('siswa_id'));
        $pelanggaranIds = array_values(array_unique(array_map(
            'intval',
            (array) $request->input('pelanggaran_ids', [])
        )));

        $recommendation = $this->recommendationService->recommendForSiswa($siswa, $pelanggaranIds);

        if (! $recommendation['eligible']) {
            return $this->jsonError(
                'Siswa belum memenuhi syarat hukuman (minimal '.$recommendation['min_points'].' poin pelanggaran).'
            );
        }

        if ($recommendation['violations'] === []) {
            return $this->jsonError('Pilih minimal satu pelanggaran yang masih aktif untuk dihukum.');
        }

        $record = DB::transaction(function () use ($request, $siswa, $recommendation) {
            $hukuman = HukumanSiswa::create([
                'siswa_id' => $siswa->id,
                'sekolah_id' => $siswa->sekolah_id,
                'total_point' => $recommendation['total_point'],
                'recommended_sanction' => $recommendation['recommended_sanction'],
                'sanction' => trim($request->string('sanction')->toString()),
                'status' => HukumanStatus::normalize($request->input('status')) ?? HukumanStatus::DITERBITKAN,
                'keterangan' => $request->input('keterangan'),
                'tanggal' => $request->input('tanggal'),
                'processed_by_user_id' => $request->user()->id,
            ]);

            $validIds = collect($recommendation['violations'])->pluck('id')->all();
            $hukuman->pelanggaranSiswa()->sync($validIds);

            PelanggaranSiswa::query()
                ->whereIn('id', $validIds)
                ->each(fn (PelanggaranSiswa $row) => $row->markAsPunished());

            $this->handleBuktiUpload($request, $hukuman);

            return $hukuman;
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('Hukuman siswa diterbitkan', ActionMessage::siswa($siswa)),
            $record
        );
    }

    public function show(HukumanSiswa $hukumanSiswa): JsonResponse
    {
        $this->authorize('hukuman-siswa.view');

        $hukumanSiswa->load([
            'siswa.kelas',
            'processedBy',
            'pelanggaranSiswa.jenisPelanggaran',
            'buktiCatatan',
        ]);

        return $this->jsonSuccess('OK', [
            'id' => $hukumanSiswa->id,
            'siswa_id' => $hukumanSiswa->siswa_id,
            'siswa_name' => $hukumanSiswa->siswa?->name,
            'siswa_nis' => $hukumanSiswa->siswa?->nis,
            'siswa_kelas' => $hukumanSiswa->siswa?->kelas?->name,
            'total_point' => $hukumanSiswa->total_point,
            'recommended_sanction' => $hukumanSiswa->recommended_sanction,
            'recommended_label' => $hukumanSiswa->recommendedSanctionLabel(),
            'sanction' => $hukumanSiswa->sanction,
            'sanction_label' => $hukumanSiswa->sanctionLabel(),
            'status' => $hukumanSiswa->status,
            'status_label' => $hukumanSiswa->statusLabel(),
            'keterangan' => $hukumanSiswa->keterangan,
            'tanggal' => $hukumanSiswa->tanggal->format('Y-m-d'),
            'processed_by_name' => $hukumanSiswa->processedBy?->name,
            'violations' => $hukumanSiswa->pelanggaranSiswa->map(fn (PelanggaranSiswa $row) => [
                'id' => $row->id,
                'judul' => $row->judul,
                'tanggal' => $row->tanggal->format('Y-m-d'),
                'point' => $row->point,
                'jenis_nama' => $row->jenisPelanggaran?->nama,
            ])->values()->all(),
            'bukti' => $hukumanSiswa->buktiCatatan->map(fn ($b) => [
                'id' => $b->id,
                'url' => $b->url,
                'original_name' => $b->original_name,
                'is_image' => $b->isImage(),
                'is_pdf' => $b->isPdf(),
            ]),
        ]);
    }

    public function update(StoreHukumanSiswaRequest $request, HukumanSiswa $hukumanSiswa): JsonResponse
    {
        DB::transaction(function () use ($request, $hukumanSiswa) {
            $hukumanSiswa->update([
                'sanction' => trim($request->string('sanction')->toString()),
                'status' => HukumanStatus::normalize($request->input('status')) ?? $hukumanSiswa->status,
                'keterangan' => $request->input('keterangan'),
                'tanggal' => $request->input('tanggal'),
            ]);

            $this->handleBuktiUpload($request, $hukumanSiswa);

            $deletedIds = $request->input('bukti_delete', []);
            if (! empty($deletedIds)) {
                $hukumanSiswa->buktiCatatan()->whereIn('id', $deletedIds)->each(function (BuktiCatatan $bukti) {
                    Storage::disk('public')->delete($bukti->file_path);
                    $bukti->delete();
                });
            }
        });

        $hukumanSiswa->load('siswa');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Hukuman siswa diperbarui', ActionMessage::siswa($hukumanSiswa->siswa))
        );
    }

    public function destroy(HukumanSiswa $hukumanSiswa): JsonResponse
    {
        $this->authorize('hukuman-siswa.delete');

        $siswa = $hukumanSiswa->siswa;

        DB::transaction(function () use ($hukumanSiswa) {
            $linked = $hukumanSiswa->pelanggaranSiswa()->get();
            foreach ($linked as $pelanggaran) {
                $pelanggaran->restoreFromPunishment();
            }

            $hukumanSiswa->buktiCatatan()->each(function (BuktiCatatan $bukti) {
                Storage::disk('public')->delete($bukti->file_path);
                $bukti->delete();
            });
            $hukumanSiswa->pelanggaranSiswa()->detach();
            $hukumanSiswa->delete();
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject('Hukuman siswa dihapus', ActionMessage::siswa($siswa))
        );
    }

    private function handleBuktiUpload(Request $request, HukumanSiswa $record): void
    {
        if (! $request->hasFile('bukti')) {
            return;
        }

        $directory = "bukti-catatan/hukuman-siswa/{$record->id}";

        foreach ($request->file('bukti') as $file) {
            if (! BuktiCatatanRules::validateFile($file)) {
                continue;
            }

            $isImage = $this->isImageUpload($file->getClientOriginalExtension());
            if ($isImage) {
                $path = ImageConverter::storeAsWebp($file, $directory, 'public', 85);
                $fileType = 'webp';
                $fileSize = Storage::disk('public')->size($path) ?: $file->getSize();
            } else {
                $filename = time().'_'.mt_rand(1000, 9999).'_'.$file->getClientOriginalName();
                $path = $file->storeAs($directory, $filename, 'public');
                $fileType = $file->getClientOriginalExtension();
                $fileSize = $file->getSize();
            }

            BuktiCatatan::create([
                'buktiable_type' => HukumanSiswa::class,
                'buktiable_id' => $record->id,
                'file_path' => $path,
                'file_type' => $fileType,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $fileSize,
            ]);
        }
    }

    private function isImageUpload(string $extension): bool
    {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}
