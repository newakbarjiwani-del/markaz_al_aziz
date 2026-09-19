<?php

namespace App\Http\Controllers\Admin\Ujian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\StoreUjianRequest;
use App\Http\Requests\Ujian\StoreUjianSoalRequest;
use App\Http\Requests\Ujian\UpdateUjianRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAkademik;
use App\Models\Ujian;
use App\Models\UjianSoal;
use App\Support\AdminSchoolScope;
use App\Support\AkademikSemester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UjianController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('ujian.view');

        return view('admin.ujian.index', [
            'title' => 'Daftar Ujian',
            'schools' => AdminSchoolScope::schools(),
            'tahunAkademik' => TahunAkademik::query()->orderByDesc('name')->get(),
            'kelas' => Kelas::query()->orderBy('name')->get(),
            'mapel' => MataPelajaran::query()->orderBy('name')->get(),
            'guru' => Guru::query()->orderBy('name')->get(),
            'semesters' => AkademikSemester::labels(),
            'statuses' => Ujian::statusLabels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('ujian.view');

        $query = Ujian::query()
            ->with(['sekolah', 'tahunAkademik', 'kelas', 'mataPelajaran'])
            ->withCount('soal')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')));

        AdminSchoolScope::applyWithGlobal($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['title'],
            'orderable' => ['title', 'status', 'starts_at'],
        ], function (Ujian $ujian) {
            $fields = array_merge(
                $ujian->only([
                    'title', 'sekolah_id', 'tahun_akademik_id', 'semester',
                    'mata_pelajaran_id', 'kelas_id', 'guru_id', 'duration_minutes', 'max_attempts',
                ]),
                [
                    'starts_at' => $ujian->starts_at?->format('Y-m-d\TH:i'),
                    'ends_at' => $ujian->ends_at?->format('Y-m-d\TH:i'),
                ]
            );

            $showUrl = route('admin.ujian.ujian.show', $ujian);

            $actions = [
                'edit' => [
                    'update_url' => route('admin.ujian.ujian.update', $ujian),
                    'form_target' => 'ujian-form',
                    'modal_target' => 'ujian-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.ujian.ujian.destroy', $ujian),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus ujian ini?',
                    'confirm_detail' => [
                        ['label' => 'Judul', 'value' => $ujian->title],
                        ['label' => 'Soal', 'value' => (string) $ujian->soal_count],
                    ],
                ],
            ];

            $statusLabel = Ujian::statusLabels()[$ujian->status] ?? $ujian->status;
            $statusClass = match ($ujian->status) {
                Ujian::STATUS_PUBLISHED => 'badge badge-green',
                Ujian::STATUS_CLOSED => 'badge badge-red',
                default => 'badge badge-slate',
            };

            return [
                $this->cell(
                    '<a href="'.e($showUrl).'" class="font-medium text-primary-700 hover:underline dark:text-primary-300">'.e($ujian->title).'</a>',
                    $ujian->title,
                    'text'
                ),
                $ujian->tahunAkademik?->name ?? '-',
                AkademikSemester::label($ujian->semester),
                $ujian->kelas?->name ?? 'Semua kelas',
                $ujian->soal_count,
                $this->badgeCell($statusLabel, $statusClass),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreUjianRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['status'] = Ujian::STATUS_DRAFT;
        $data['max_attempts'] = (int) ($data['max_attempts'] ?? 1);

        $ujian = Ujian::create($data);

        return $this->jsonSuccess('Ujian berhasil ditambahkan.', $ujian, 201);
    }

    public function update(UpdateUjianRequest $request, Ujian $ujian): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['max_attempts'] = (int) ($data['max_attempts'] ?? 1);

        $ujian->update($data);

        return $this->jsonSuccess('Ujian berhasil diperbarui.', $ujian->fresh());
    }

    public function destroy(Ujian $ujian): JsonResponse
    {
        $this->authorize('ujian.delete');

        $title = $ujian->title;
        $ujian->delete();

        return $this->jsonSuccess('Ujian "'.$title.'" berhasil dihapus.');
    }

    public function show(Ujian $ujian): View
    {
        $this->authorize('ujian.view');

        $ujian->load(['sekolah', 'tahunAkademik', 'kelas', 'mataPelajaran', 'guru', 'soal']);

        return view('admin.ujian.show', [
            'title' => $ujian->title,
            'ujian' => $ujian,
            'jenisLabels' => UjianSoal::jenisLabels(),
            'statusLabels' => Ujian::statusLabels(),
        ]);
    }

    public function publish(Ujian $ujian): JsonResponse
    {
        $this->authorize('ujian.update');

        if ($ujian->soal()->doesntExist()) {
            return response()->json([
                'success' => false,
                'message' => 'Tambahkan soal sebelum mempublikasikan.',
            ], 422);
        }

        $ujian->update(['status' => Ujian::STATUS_PUBLISHED]);

        return $this->jsonSuccess('Ujian dipublikasikan.', $ujian->fresh());
    }

    public function close(Ujian $ujian): JsonResponse
    {
        $this->authorize('ujian.update');

        $ujian->update(['status' => Ujian::STATUS_CLOSED]);

        return $this->jsonSuccess('Ujian ditutup.', $ujian->fresh());
    }

    public function storeSoal(StoreUjianSoalRequest $request, Ujian $ujian): JsonResponse
    {
        $data = $request->validated();

        if (($data['jenis'] ?? '') === UjianSoal::JENIS_ESSAY) {
            $data['opsi'] = null;
            $data['kunci'] = null;
        }

        $data['sort_order'] = (int) ($data['sort_order'] ?? (($ujian->soal()->max('sort_order') ?? 0) + 1));

        $soal = $ujian->soal()->create($data);

        return $this->jsonSuccess('Soal ditambahkan.', $soal, 201);
    }

    public function destroySoal(Ujian $ujian, UjianSoal $soal): JsonResponse
    {
        $this->authorize('ujian.delete');

        if ((int) $soal->ujian_id !== (int) $ujian->id) {
            abort(404);
        }

        $soal->delete();

        return $this->jsonSuccess('Soal dihapus.');
    }
}
