<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akademik\StoreNilaiEntryRequest;
use App\Http\Requests\Akademik\UpdateNilaiEntryRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\MataPelajaran;
use App\Models\NilaiEntry;
use App\Models\Siswa;
use App\Models\TahunAkademik;
use App\Support\AdminSchoolScope;
use App\Support\AkademikSemester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NilaiController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('akademik.view');

        $mapelQuery = MataPelajaran::query()->where('is_active', true)->orderBy('name');
        AdminSchoolScope::applyWithGlobal($mapelQuery);

        $siswaQuery = Siswa::query()->where('status', Siswa::STATUS_ACTIVE)->orderBy('name');
        AdminSchoolScope::apply($siswaQuery);

        return view('admin.akademik.nilai', [
            'title' => 'Nilai',
            'tahunAkademik' => TahunAkademik::query()->orderByDesc('name')->get(),
            'mapelOptions' => $mapelQuery->get(),
            'siswaOptions' => $siswaQuery->limit(500)->get(),
            'semesters' => AkademikSemester::labels(),
            'jenisLabels' => NilaiEntry::jenisLabels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('akademik.view');

        $query = NilaiEntry::query()
            ->with(['siswa.kelas', 'mataPelajaran', 'tahunAkademik', 'kompetensiDasar'])
            ->when($request->filled('siswa_id'), fn ($q) => $q->where('siswa_id', $request->integer('siswa_id')))
            ->when($request->filled('mata_pelajaran_id'), fn ($q) => $q->where('mata_pelajaran_id', $request->integer('mata_pelajaran_id')))
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')))
            ->when($request->filled('semester'), fn ($q) => $q->where('semester', $request->string('semester')));

        AdminSchoolScope::applyRelation($query, 'siswa');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['catatan', 'jenis'],
            'orderable' => ['skor', 'jenis', 'semester', 'created_at'],
        ], function (NilaiEntry $entry) {
            $fields = array_merge(
                $entry->only([
                    'siswa_id',
                    'mata_pelajaran_id',
                    'tahun_akademik_id',
                    'semester',
                    'jenis',
                    'kompetensi_dasar_id',
                    'skor',
                    'catatan',
                ]),
                ['skor' => (string) $entry->skor]
            );

            $actions = [
                'edit' => [
                    'update_url' => route('admin.akademik.nilai.update', $entry),
                    'form_target' => 'akademik-nilai-form',
                    'modal_target' => 'akademik-nilai-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.akademik.nilai.destroy', $entry),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus entri nilai ini?',
                    'confirm_detail' => [
                        ['label' => 'Siswa', 'value' => $entry->siswa?->name ?? '-'],
                        ['label' => 'Mapel', 'value' => $entry->mataPelajaran?->name ?? '-'],
                        ['label' => 'Skor', 'value' => (string) $entry->skor],
                    ],
                ],
            ];

            return [
                $entry->siswa?->name ?? '-',
                $entry->mataPelajaran?->name ?? '-',
                $entry->tahunAkademik?->name ?? '-',
                AkademikSemester::label($entry->semester),
                $entry->jenisLabel(),
                $entry->skor,
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreNilaiEntryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['recorded_by'] = $request->user()->id;
        $data['kompetensi_dasar_id'] = $request->filled('kompetensi_dasar_id')
            ? $request->integer('kompetensi_dasar_id')
            : null;

        $entry = NilaiEntry::create($data);

        return $this->jsonSuccess('Nilai berhasil ditambahkan.', $entry, 201);
    }

    public function update(UpdateNilaiEntryRequest $request, NilaiEntry $nilai): JsonResponse
    {
        $data = $request->validated();
        $data['kompetensi_dasar_id'] = $request->filled('kompetensi_dasar_id')
            ? $request->integer('kompetensi_dasar_id')
            : null;

        $nilai->update($data);

        return $this->jsonSuccess('Nilai berhasil diperbarui.', $nilai->fresh());
    }

    public function destroy(NilaiEntry $nilai): JsonResponse
    {
        $this->authorize('akademik.delete');

        $nilai->delete();

        return $this->jsonSuccess('Entri nilai berhasil dihapus.');
    }
}
