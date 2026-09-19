<?php

namespace App\Http\Controllers\Admin\TeacherManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StorePrestasiGuruRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\BuktiCatatan;
use App\Models\JenisPrestasi;
use App\Models\PrestasiGuru;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\BuktiCatatanRules;
use App\Support\ImageConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PrestasiGuruController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.prestasi-pelanggaran.prestasi-guru', [
            'title' => 'Prestasi Guru',
            'schools' => AdminSchoolScope::schools(),
            'katalogOptions' => JenisPrestasi::query()->active()->ordered()->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PrestasiGuru::query()
            ->with(['guru.sekolah', 'sekolah', 'reportedBy'])
            ->select('prestasi_guru.*');

        AdminSchoolScope::apply($query);

        if ($request->filled('guru_id')) {
            $query->where('prestasi_guru.guru_id', $request->integer('guru_id'));
        }

        if ($request->filled('sekolah_id')) {
            $query->where('prestasi_guru.sekolah_id', $request->integer('sekolah_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('tanggal', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('tanggal', '<=', $request->input('date_to'));
        }

        $columns = [
            'searchable' => ['judul', 'keterangan', 'guru.name', 'guru.nip'],
            'orderable' => ['judul', 'tanggal', 'point', 'judul'],
        ];

        return $this->datatableResponse($request, $query, $columns, function (PrestasiGuru $row) {
            $buktiCount = $row->buktiCatatan()->count();

            $actions = [
                'edit' => [
                    'update_url' => route('admin.prestasi-pelanggaran.prestasi-guru.update', $row),
                    'form_target' => 'prestasi-guru-form',
                    'modal_target' => 'prestasi-guru-modal',
                    'record' => array_merge($row->only(['guru_id', 'judul', 'keterangan', 'point', 'jenis_prestasi_id']), [
                        'tanggal' => $row->tanggal->format('Y-m-d'),
                        'jenis_nama' => $row->jenisPrestasi?->nama,
                        'jenis_text' => $row->jenisPrestasi
                            ? $row->jenisPrestasi->nama.($row->jenisPrestasi->bidang ? ' — '.$row->jenisPrestasi->bidang : '')
                            : null,
                        'show_url' => route('admin.prestasi-pelanggaran.prestasi-guru.show', $row),
                    ]),
                ],
                'view' => [
                    'url' => route('admin.prestasi-pelanggaran.prestasi-guru.show', $row),
                    'modal_target' => 'prestasi-guru-detail-modal',
                ],
                'delete' => [
                    'url' => route('admin.prestasi-pelanggaran.prestasi-guru.destroy', $row),
                    'confirm_title' => 'Hapus Prestasi',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus prestasi ini?',
                    'confirm_detail' => [
                        ['label' => 'Judul', 'value' => $row->judul],
                        ['label' => 'Guru', 'value' => $row->guru?->name ?? '-'],
                    ],
                ],
            ];

            return [
                $row->guru?->name ?? '-',
                $row->guru?->nip ?? '-',
                $row->guru?->jabatan ?? '-',
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

    public function store(StorePrestasiGuruRequest $request): JsonResponse
    {
        $sekolahId = AdminSchoolScope::resolveForStore($request);

        $record = DB::transaction(function () use ($request, $sekolahId) {
            $record = PrestasiGuru::create([
                'guru_id' => $request->integer('guru_id'),
                'sekolah_id' => $sekolahId,
                'jenis_prestasi_id' => $request->filled('jenis_prestasi_id') ? $request->integer('jenis_prestasi_id') : null,
                'judul' => $request->input('judul'),
                'keterangan' => $request->input('keterangan'),
                'tanggal' => $request->input('tanggal'),
                'point' => $request->integer('point'),
                'reported_by' => $request->user()->id,
            ]);

            $this->handleBuktiUpload($request, $record);

            return $record;
        });

        $guru = $record->guru;
        $subject = ActionMessage::guru($guru);

        return $this->jsonSuccess(ActionMessage::withSubject('Prestasi guru ditambahkan', $subject), $record);
    }

    public function show(PrestasiGuru $prestasiGuru): JsonResponse
    {
        $this->authorize('prestasi-guru.view');

        $prestasiGuru->load(['guru.sekolah', 'sekolah', 'reportedBy', 'buktiCatatan', 'jenisPrestasi']);

        return $this->jsonSuccess('OK', [
            'id' => $prestasiGuru->id,
            'guru_id' => $prestasiGuru->guru_id,
            'guru_name' => $prestasiGuru->guru?->name,
            'guru_nip' => $prestasiGuru->guru?->nip,
            'guru_jabatan' => $prestasiGuru->guru?->jabatan,
            'sekolah_name' => $prestasiGuru->sekolah?->name,
            'judul' => $prestasiGuru->judul,
            'keterangan' => $prestasiGuru->keterangan,
            'tanggal' => $prestasiGuru->tanggal->format('Y-m-d'),
            'point' => $prestasiGuru->point,
            'jenis_prestasi_id' => $prestasiGuru->jenis_prestasi_id,
            'jenis_nama' => $prestasiGuru->jenisPrestasi?->nama,
            'jenis_point' => $prestasiGuru->jenisPrestasi?->point,
            'reported_by_name' => $prestasiGuru->reportedBy?->name,
            'bukti' => $prestasiGuru->buktiCatatan->map(fn ($b) => [
                'id' => $b->id,
                'url' => $b->url,
                'original_name' => $b->original_name,
                'is_image' => $b->isImage(),
                'is_pdf' => $b->isPdf(),
            ]),
        ]);
    }

    public function update(StorePrestasiGuruRequest $request, PrestasiGuru $prestasiGuru): JsonResponse
    {
        DB::transaction(function () use ($request, $prestasiGuru) {
            $prestasiGuru->update([
                'guru_id' => $request->integer('guru_id'),
                'jenis_prestasi_id' => $request->filled('jenis_prestasi_id') ? $request->integer('jenis_prestasi_id') : null,
                'judul' => $request->input('judul'),
                'keterangan' => $request->input('keterangan'),
                'tanggal' => $request->input('tanggal'),
                'point' => $request->integer('point'),
            ]);

            $this->handleBuktiUpload($request, $prestasiGuru);

            $deletedIds = $request->input('bukti_delete', []);
            if (! empty($deletedIds)) {
                $prestasiGuru->buktiCatatan()->whereIn('id', $deletedIds)->each(function (BuktiCatatan $bukti) {
                    Storage::disk('public')->delete($bukti->file_path);
                    $bukti->delete();
                });
            }
        });

        $guru = $prestasiGuru->guru;
        $subject = ActionMessage::guru($guru);

        return $this->jsonSuccess(ActionMessage::withSubject('Prestasi guru diperbarui', $subject));
    }

    public function destroy(PrestasiGuru $prestasiGuru): JsonResponse
    {
        $this->authorize('prestasi-guru.delete');

        $guru = $prestasiGuru->guru;
        $subject = ActionMessage::guru($guru);

        DB::transaction(function () use ($prestasiGuru) {
            $prestasiGuru->buktiCatatan()->each(function (BuktiCatatan $bukti) {
                Storage::disk('public')->delete($bukti->file_path);
                $bukti->delete();
            });
            $prestasiGuru->delete();
        });

        return $this->jsonSuccess(ActionMessage::withSubject('Prestasi guru dihapus', $subject));
    }

    private function handleBuktiUpload(Request $request, PrestasiGuru $record): void
    {
        if (! $request->hasFile('bukti')) {
            return;
        }

        $directory = "bukti-catatan/prestasi-guru/{$record->id}";

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
                'buktiable_type' => PrestasiGuru::class,
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
