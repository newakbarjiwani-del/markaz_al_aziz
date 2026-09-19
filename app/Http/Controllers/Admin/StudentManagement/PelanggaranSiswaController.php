<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StorePelanggaranSiswaRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\ResolvesSiswaIdsFromRequest;
use App\Models\BuktiCatatan;
use App\Models\JenisPelanggaran;
use App\Models\PelanggaranSiswa;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\BuktiCatatanRules;
use App\Support\ImageConverter;
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
        return view('admin.prestasi-pelanggaran.pelanggaran-siswa', [
            'title' => 'Pelanggaran Siswa',
            'schools' => AdminSchoolScope::schools(),
            'katalogOptions' => JenisPelanggaran::query()->active()->ordered()->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PelanggaranSiswa::query()
            ->with(['siswa.kelas', 'sekolah', 'reportedBy'])
            ->select('pelanggaran_siswa.*');

        AdminSchoolScope::apply($query);

        if ($request->filled('siswa_id')) {
            $query->where('pelanggaran_siswa.siswa_id', $request->integer('siswa_id'));
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->integer('kelas_id')));
        }

        if ($request->filled('sekolah_id')) {
            $query->where('pelanggaran_siswa.sekolah_id', $request->integer('sekolah_id'));
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

            $actions = [
                'edit' => [
                    'update_url' => route('admin.prestasi-pelanggaran.pelanggaran-siswa.update', $row),
                    'form_target' => 'pelanggaran-siswa-form',
                    'modal_target' => 'pelanggaran-siswa-modal',
                    'record' => array_merge($row->only(['siswa_id', 'judul', 'keterangan', 'point', 'jenis_pelanggaran_id']), [
                        'tanggal' => $row->tanggal->format('Y-m-d'),
                        'jenis_nama' => $row->jenisPelanggaran?->nama,
                        'jenis_text' => $row->jenisPelanggaran
                            ? $row->jenisPelanggaran->nama.' — '.$row->jenisPelanggaran->bidang.' ('.$row->jenisPelanggaran->levelLabel().')'
                            : null,
                        'show_url' => route('admin.prestasi-pelanggaran.pelanggaran-siswa.show', $row),
                    ]),
                ],
                'view' => [
                    'url' => route('admin.prestasi-pelanggaran.pelanggaran-siswa.show', $row),
                    'modal_target' => 'pelanggaran-siswa-detail-modal',
                ],
                'delete' => [
                    'url' => route('admin.prestasi-pelanggaran.pelanggaran-siswa.destroy', $row),
                    'confirm_title' => 'Hapus Pelanggaran',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus pelanggaran ini?',
                    'confirm_detail' => [
                        ['label' => 'Judul', 'value' => $row->judul],
                        ['label' => 'Siswa', 'value' => $row->siswa?->name ?? '-'],
                    ],
                ],
            ];

            return [
                $row->siswa?->name ?? '-',
                $row->siswa?->nis ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $row->is_punished
                    ? $this->cell(
                        e($row->judul).' <span class="badge badge-neutral ml-1">Sudah dihukum</span>',
                        $row->judul,
                        'html'
                    )
                    : $this->cell($row->judul, $row->judul),
                $this->dateCell($row->tanggal),
                $row->is_punished && $row->point_asli !== null
                    ? $this->cell('0 <span class="text-muted text-xs">(asli: '.$row->point_asli.')</span>', 0, 'html')
                    : $row->point,
                $buktiCount > 0
                    ? $this->cell("<span class='badge bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'>{$buktiCount} file</span>", $buktiCount, 'html')
                    : '-',
                $this->cell('', ['actions' => $actions], 'action'),
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
                    'point' => $request->integer('point'),
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

        $pelanggaranSiswa->load(['siswa.kelas', 'sekolah', 'reportedBy', 'buktiCatatan', 'jenisPelanggaran']);

        return $this->jsonSuccess('OK', [
            'id' => $pelanggaranSiswa->id,
            'siswa_id' => $pelanggaranSiswa->siswa_id,
            'siswa_name' => $pelanggaranSiswa->siswa?->name,
            'siswa_nis' => $pelanggaranSiswa->siswa?->nis,
            'siswa_kelas' => $pelanggaranSiswa->siswa?->kelas?->name,
            'sekolah_name' => $pelanggaranSiswa->sekolah?->name,
            'judul' => $pelanggaranSiswa->judul,
            'keterangan' => $pelanggaranSiswa->keterangan,
            'tanggal' => $pelanggaranSiswa->tanggal->format('Y-m-d'),
            'point' => $pelanggaranSiswa->point,
            'is_punished' => $pelanggaranSiswa->is_punished,
            'point_asli' => $pelanggaranSiswa->point_asli,
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

    public function update(StorePelanggaranSiswaRequest $request, PelanggaranSiswa $pelanggaranSiswa): JsonResponse
    {
        DB::transaction(function () use ($request, $pelanggaranSiswa) {
            $pelanggaranSiswa->update([
                'siswa_id' => $request->integer('siswa_id'),
                'jenis_pelanggaran_id' => $request->filled('jenis_pelanggaran_id') ? $request->integer('jenis_pelanggaran_id') : null,
                'judul' => $request->input('judul'),
                'keterangan' => $request->input('keterangan'),
                'tanggal' => $request->input('tanggal'),
                'point' => $request->integer('point'),
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

        $siswa = $pelanggaranSiswa->siswa;
        $subject = ActionMessage::siswa($siswa);

        return $this->jsonSuccess(ActionMessage::withSubject('Pelanggaran siswa diperbarui', $subject));
    }

    public function destroy(PelanggaranSiswa $pelanggaranSiswa): JsonResponse
    {
        $this->authorize('pelanggaran-siswa.delete');

        $siswa = $pelanggaranSiswa->siswa;
        $subject = ActionMessage::siswa($siswa);

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
                'buktiable_type' => PelanggaranSiswa::class,
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
