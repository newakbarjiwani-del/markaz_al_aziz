<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StorePrestasiSiswaRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\ResolvesSiswaIdsFromRequest;
use App\Models\BuktiCatatan;
use App\Models\JenisPrestasi;
use App\Models\PrestasiSiswa;
use App\Support\ActionMessage;
use App\Support\BuktiCatatanRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PrestasiSiswaController extends Controller
{
    use DataTableTrait;
    use ResolvesSiswaIdsFromRequest;

    public function index(): View
    {
        return view('portal.guru.prestasi-siswa', [
            'title' => 'Prestasi Siswa',
            'katalogOptions' => JenisPrestasi::query()->active()->ordered()->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('prestasi-siswa.view');

        $query = PrestasiSiswa::query()
            ->with(['siswa.kelas', 'reportedBy'])
            ->select('prestasi_siswa.*');

        if ($request->filled('siswa_id')) {
            $query->where('siswa_id', $request->integer('siswa_id'));
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->integer('kelas_id')));
        }

        if ($request->filled('date_from')) {
            $query->where('tanggal', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('tanggal', '<=', $request->input('date_to'));
        }

        if (! $request->has('order.0.column')) {
            $query->orderByDesc('point')->orderByDesc('tanggal');
        }

        $columns = [
            'searchable' => ['judul', 'keterangan', 'siswa.name', 'siswa.nis'],
            'orderable' => [null, null, null, 'judul', 'tanggal', 'point'],
        ];

        return $this->datatableResponse($request, $query, $columns, function (PrestasiSiswa $row) {
            $buktiCount = $row->buktiCatatan()->count();

            return [
                $row->siswa?->name ?? '-',
                $row->siswa?->nis ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $this->cell($row->judul, $row->judul),
                $this->dateCell($row->tanggal),
                $row->point,
                $buktiCount > 0
                    ? $this->cell("<span class='badge bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'>{$buktiCount} file</span>", $buktiCount, 'html')
                    : '-',
                $this->cell(
                    '<div class="flex items-center gap-1">'
                    .'<button type="button" class="btn-action btn-action--icon prestasi-edit-btn" title="Edit">'
                    .'<i class="ti ti-pencil"></i></button>'
                    .'<button type="button" class="btn-action btn-action--icon prestasi-detail-btn" title="Detail">'
                    .'<i class="ti ti-eye"></i></button>'
                    .'<button type="button" class="btn-action btn-action--icon prestasi-delete-btn text-red-500" title="Hapus">'
                    .'<i class="ti ti-trash"></i></button>'
                    .'</div>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function store(StorePrestasiSiswaRequest $request): JsonResponse
    {
        $siswaList = $this->siswaListFromRequest($request);

        DB::transaction(function () use ($request, $siswaList) {
            foreach ($siswaList as $siswa) {
                $record = PrestasiSiswa::create([
                    'siswa_id' => $siswa->id,
                    'sekolah_id' => $siswa->sekolah_id,
                    'jenis_prestasi_id' => $request->filled('jenis_prestasi_id') ? $request->integer('jenis_prestasi_id') : null,
                    'judul' => $request->input('judul'),
                    'keterangan' => $request->input('keterangan'),
                    'tanggal' => $request->input('tanggal'),
                    'point' => $request->integer('point', 0),
                    'reported_by' => $request->user()->id,
                ]);

                $this->handleBuktiUpload($request, $record);
            }
        });

        $count = count($siswaList);
        $message = $count === 1
            ? ActionMessage::withSubject('Prestasi siswa ditambahkan', ActionMessage::siswa($siswaList[0]))
            : "Prestasi ditambahkan untuk {$count} siswa.";

        return $this->jsonSuccess($message);
    }

    public function show(PrestasiSiswa $prestasiSiswa): JsonResponse
    {
        $this->authorize('prestasi-siswa.view');

        $prestasiSiswa->load(['siswa.kelas', 'reportedBy', 'buktiCatatan', 'jenisPrestasi']);

        return $this->jsonSuccess('OK', [
            'id' => $prestasiSiswa->id,
            'siswa_id' => $prestasiSiswa->siswa_id,
            'siswa_name' => $prestasiSiswa->siswa?->name,
            'siswa_nis' => $prestasiSiswa->siswa?->nis,
            'siswa_kelas' => $prestasiSiswa->siswa?->kelas?->name,
            'judul' => $prestasiSiswa->judul,
            'keterangan' => $prestasiSiswa->keterangan,
            'tanggal' => $prestasiSiswa->tanggal->format('Y-m-d'),
            'point' => $prestasiSiswa->point,
            'jenis_prestasi_id' => $prestasiSiswa->jenis_prestasi_id,
            'jenis_nama' => $prestasiSiswa->jenisPrestasi?->nama,
            'jenis_point' => $prestasiSiswa->jenisPrestasi?->point,
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

    public function update(StorePrestasiSiswaRequest $request, PrestasiSiswa $prestasiSiswa): JsonResponse
    {
        DB::transaction(function () use ($request, $prestasiSiswa) {
            $prestasiSiswa->update([
                'siswa_id' => $request->integer('siswa_id'),
                'jenis_prestasi_id' => $request->filled('jenis_prestasi_id') ? $request->integer('jenis_prestasi_id') : null,
                'judul' => $request->input('judul'),
                'keterangan' => $request->input('keterangan'),
                'tanggal' => $request->input('tanggal'),
                'point' => $request->integer('point', 0),
            ]);

            $this->handleBuktiUpload($request, $prestasiSiswa);

            $deletedIds = $request->input('bukti_delete', []);
            if (! empty($deletedIds)) {
                $prestasiSiswa->buktiCatatan()->whereIn('id', $deletedIds)->each(function (BuktiCatatan $bukti) {
                    Storage::disk('public')->delete($bukti->file_path);
                    $bukti->delete();
                });
            }
        });

        $prestasiSiswa->load('siswa');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Prestasi siswa diperbarui', ActionMessage::siswa($prestasiSiswa->siswa))
        );
    }

    public function destroy(PrestasiSiswa $prestasiSiswa): JsonResponse
    {
        $this->authorize('prestasi-siswa.delete');

        $subject = ActionMessage::siswa($prestasiSiswa->siswa);

        DB::transaction(function () use ($prestasiSiswa) {
            $prestasiSiswa->buktiCatatan()->each(function (BuktiCatatan $bukti) {
                Storage::disk('public')->delete($bukti->file_path);
                $bukti->delete();
            });
            $prestasiSiswa->delete();
        });

        return $this->jsonSuccess(ActionMessage::withSubject('Prestasi siswa dihapus', $subject));
    }

    private function handleBuktiUpload(Request $request, PrestasiSiswa $record): void
    {
        if (! $request->hasFile('bukti')) {
            return;
        }

        $directory = "bukti-catatan/prestasi-siswa/{$record->id}";

        foreach ($request->file('bukti') as $file) {
            if (! BuktiCatatanRules::validateFile($file)) {
                continue;
            }

            $filename = time().'_'.mt_rand(1000, 9999).'_'.$file->getClientOriginalName();
            $path = $file->storeAs($directory, $filename, 'public');

            BuktiCatatan::create([
                'buktiable_type' => PrestasiSiswa::class,
                'buktiable_id' => $record->id,
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);
        }
    }
}
