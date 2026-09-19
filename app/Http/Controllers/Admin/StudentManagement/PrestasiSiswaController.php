<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StorePrestasiSiswaRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\ResolvesSiswaIdsFromRequest;
use App\Models\BuktiCatatan;
use App\Models\JenisPrestasi;
use App\Models\PrestasiSiswa;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\BuktiCatatanRules;
use App\Support\ImageConverter;
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
        return view('admin.prestasi-pelanggaran.prestasi-siswa', [
            'title' => 'Prestasi Siswa',
            'schools' => AdminSchoolScope::schools(),
            'katalogOptions' => JenisPrestasi::query()->active()->ordered()->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PrestasiSiswa::query()
            ->with(['siswa.kelas', 'sekolah', 'reportedBy'])
            ->select('prestasi_siswa.*');

        AdminSchoolScope::apply($query);

        if ($request->filled('siswa_id')) {
            $query->where('prestasi_siswa.siswa_id', $request->integer('siswa_id'));
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->integer('kelas_id')));
        }

        if ($request->filled('sekolah_id')) {
            $query->where('prestasi_siswa.sekolah_id', $request->integer('sekolah_id'));
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

            $actions = [
                'edit' => [
                    'update_url' => route('admin.prestasi-pelanggaran.prestasi-siswa.update', $row),
                    'form_target' => 'prestasi-siswa-form',
                    'modal_target' => 'prestasi-siswa-modal',
                    'record' => array_merge($row->only(['siswa_id', 'judul', 'keterangan', 'point', 'jenis_prestasi_id']), [
                        'tanggal' => $row->tanggal->format('Y-m-d'),
                        'jenis_nama' => $row->jenisPrestasi?->nama,
                        'jenis_text' => $row->jenisPrestasi
                            ? $row->jenisPrestasi->nama.($row->jenisPrestasi->bidang ? ' — '.$row->jenisPrestasi->bidang : '')
                            : null,
                        'show_url' => route('admin.prestasi-pelanggaran.prestasi-siswa.show', $row),
                    ]),
                ],
                'view' => [
                    'url' => route('admin.prestasi-pelanggaran.prestasi-siswa.show', $row),
                    'modal_target' => 'prestasi-siswa-detail-modal',
                ],
                'delete' => [
                    'url' => route('admin.prestasi-pelanggaran.prestasi-siswa.destroy', $row),
                    'confirm_title' => 'Hapus Prestasi',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus prestasi ini?',
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
                $this->cell($row->judul, $row->judul),
                $this->dateCell($row->tanggal),
                $row->point,
                $buktiCount > 0
                    ? $this->cell("<span class='badge bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'>{$buktiCount} file</span>", $buktiCount, 'html')
                    : '-',
                $this->cell('', ['actions' => $actions], 'action'),
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
                    'point' => $request->integer('point'),
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

        $prestasiSiswa->load(['siswa.kelas', 'sekolah', 'reportedBy', 'buktiCatatan', 'jenisPrestasi']);

        return $this->jsonSuccess('OK', [
            'id' => $prestasiSiswa->id,
            'siswa_id' => $prestasiSiswa->siswa_id,
            'siswa_name' => $prestasiSiswa->siswa?->name,
            'siswa_nis' => $prestasiSiswa->siswa?->nis,
            'siswa_kelas' => $prestasiSiswa->siswa?->kelas?->name,
            'sekolah_name' => $prestasiSiswa->sekolah?->name,
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
                'point' => $request->integer('point'),
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

        $siswa = $prestasiSiswa->siswa;
        $subject = ActionMessage::siswa($siswa);

        return $this->jsonSuccess(ActionMessage::withSubject('Prestasi siswa diperbarui', $subject));
    }

    public function destroy(PrestasiSiswa $prestasiSiswa): JsonResponse
    {
        $this->authorize('prestasi-siswa.delete');

        $siswa = $prestasiSiswa->siswa;
        $subject = ActionMessage::siswa($siswa);

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
                'buktiable_type' => PrestasiSiswa::class,
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
