<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StorePelanggaranSiswaRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\ResolvesSiswaIdsFromRequest;
use App\Models\BuktiCatatan;
use App\Models\JenisPelanggaran;
use App\Models\PelanggaranSiswa;
use App\Support\ActionMessage;
use App\Support\BuktiCatatanRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PelanggaranSiswaController extends Controller
{
    use DataTableTrait;
    use ResolvesSiswaIdsFromRequest;

    public function index(): View
    {
        return view('portal.guru.pelanggaran-siswa', [
            'title' => 'Pelanggaran Siswa',
            'katalogOptions' => JenisPelanggaran::query()->active()->ordered()->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('pelanggaran-siswa.view');

        $query = PelanggaranSiswa::query()
            ->with(['siswa.kelas', 'reportedBy'])
            ->select('pelanggaran_siswa.*');

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

        return $this->datatableResponse($request, $query, $columns, function (PelanggaranSiswa $row) {
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
                    .'<button type="button" class="btn-action btn-action--icon pelanggaran-edit-btn" title="Edit">'
                    .'<i class="ti ti-pencil"></i></button>'
                    .'<button type="button" class="btn-action btn-action--icon pelanggaran-detail-btn" title="Detail">'
                    .'<i class="ti ti-eye"></i></button>'
                    .'<button type="button" class="btn-action btn-action--icon pelanggaran-delete-btn text-red-500" title="Hapus">'
                    .'<i class="ti ti-trash"></i></button>'
                    .'</div>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function store(StorePelanggaranSiswaRequest $request): JsonResponse
    {
        $siswaList = $this->siswaListFromRequest($request);

        DB::transaction(function () use ($request, $siswaList) {
            foreach ($siswaList as $siswa) {
                $record = PelanggaranSiswa::create([
                    'siswa_id' => $siswa->id,
                    'sekolah_id' => $siswa->sekolah_id,
                    'jenis_pelanggaran_id' => $request->integer('jenis_pelanggaran_id'),
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
            ? ActionMessage::withSubject('Pelanggaran siswa ditambahkan', ActionMessage::siswa($siswaList[0]))
            : "Pelanggaran ditambahkan untuk {$count} siswa.";

        return $this->jsonSuccess($message);
    }

    public function show(PelanggaranSiswa $pelanggaranSiswa): JsonResponse
    {
        $this->authorize('pelanggaran-siswa.view');

        $pelanggaranSiswa->load(['siswa.kelas', 'reportedBy', 'buktiCatatan', 'jenisPelanggaran']);

        return $this->jsonSuccess('OK', [
            'id' => $pelanggaranSiswa->id,
            'siswa_id' => $pelanggaranSiswa->siswa_id,
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
            'jenis_text' => $pelanggaranSiswa->jenisPelanggaran
                ? $pelanggaranSiswa->jenisPelanggaran->nama.' — '.$pelanggaranSiswa->jenisPelanggaran->bidang.' ('.$pelanggaranSiswa->jenisPelanggaran->levelLabel().')'
                : null,
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

    public function update(StorePelanggaranSiswaRequest $request, PelanggaranSiswa $pelanggaranSiswa): JsonResponse
    {
        DB::transaction(function () use ($request, $pelanggaranSiswa) {
            $pelanggaranSiswa->update([
                'siswa_id' => $request->integer('siswa_id'),
                'jenis_pelanggaran_id' => $request->filled('jenis_pelanggaran_id') ? $request->integer('jenis_pelanggaran_id') : null,
                'judul' => $request->input('judul'),
                'keterangan' => $request->input('keterangan'),
                'tanggal' => $request->input('tanggal'),
                'point' => $request->integer('point', 0),
            ]);

            $this->handleBuktiUpload($request, $pelanggaranSiswa);

            $deletedIds = $request->input('bukti_delete', []);
            if (! empty($deletedIds)) {
                $pelanggaranSiswa->buktiCatatan()->whereIn('id', $deletedIds)->each(function (BuktiCatatan $bukti) {
                    Storage::disk('public')->delete($bukti->file_path);
                    $bukti->delete();
                });
            }
        });

        $pelanggaranSiswa->load('siswa');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Pelanggaran siswa diperbarui', ActionMessage::siswa($pelanggaranSiswa->siswa))
        );
    }

    public function destroy(PelanggaranSiswa $pelanggaranSiswa): JsonResponse
    {
        $this->authorize('pelanggaran-siswa.delete');

        $subject = ActionMessage::siswa($pelanggaranSiswa->siswa);

        DB::transaction(function () use ($pelanggaranSiswa) {
            $pelanggaranSiswa->buktiCatatan()->each(function (BuktiCatatan $bukti) {
                Storage::disk('public')->delete($bukti->file_path);
                $bukti->delete();
            });
            $pelanggaranSiswa->delete();
        });

        return $this->jsonSuccess(ActionMessage::withSubject('Pelanggaran siswa dihapus', $subject));
    }

    private function handleBuktiUpload(Request $request, PelanggaranSiswa $record): void
    {
        if (! $request->hasFile('bukti')) {
            return;
        }

        $directory = "bukti-catatan/pelanggaran-siswa/{$record->id}";

        foreach ($request->file('bukti') as $file) {
            if (! BuktiCatatanRules::validateFile($file)) {
                continue;
            }

            $filename = time().'_'.mt_rand(1000, 9999).'_'.$file->getClientOriginalName();
            $path = $file->storeAs($directory, $filename, 'public');

            BuktiCatatan::create([
                'buktiable_type' => PelanggaranSiswa::class,
                'buktiable_id' => $record->id,
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);
        }
    }
}
