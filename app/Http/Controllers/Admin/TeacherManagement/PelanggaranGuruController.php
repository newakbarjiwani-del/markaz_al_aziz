<?php

namespace App\Http\Controllers\Admin\TeacherManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StorePelanggaranGuruRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\BuktiCatatan;
use App\Models\JenisPelanggaran;
use App\Models\PelanggaranGuru;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\BuktiCatatanRules;
use App\Support\ImageConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PelanggaranGuruController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.prestasi-pelanggaran.pelanggaran-guru', [
            'title' => 'Pelanggaran Guru',
            'schools' => AdminSchoolScope::schools(),
            'katalogOptions' => JenisPelanggaran::query()->active()->ordered()->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PelanggaranGuru::query()
            ->with(['guru.sekolah', 'sekolah', 'reportedBy'])
            ->select('pelanggaran_guru.*');

        AdminSchoolScope::apply($query);

        if ($request->filled('guru_id')) {
            $query->where('pelanggaran_guru.guru_id', $request->integer('guru_id'));
        }

        if ($request->filled('sekolah_id')) {
            $query->where('pelanggaran_guru.sekolah_id', $request->integer('sekolah_id'));
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

        return $this->datatableResponse($request, $query, $columns, function (PelanggaranGuru $row) {
            $buktiCount = $row->buktiCatatan()->count();

            $actions = [
                'edit' => [
                    'update_url' => route('admin.prestasi-pelanggaran.pelanggaran-guru.update', $row),
                    'form_target' => 'pelanggaran-guru-form',
                    'modal_target' => 'pelanggaran-guru-modal',
                    'record' => array_merge($row->only(['guru_id', 'judul', 'keterangan', 'point', 'jenis_pelanggaran_id']), [
                        'tanggal' => $row->tanggal->format('Y-m-d'),
                        'jenis_nama' => $row->jenisPelanggaran?->nama,
                        'jenis_text' => $row->jenisPelanggaran
                            ? $row->jenisPelanggaran->nama.' — '.$row->jenisPelanggaran->bidang.' ('.$row->jenisPelanggaran->levelLabel().')'
                            : null,
                        'show_url' => route('admin.prestasi-pelanggaran.pelanggaran-guru.show', $row),
                    ]),
                ],
                'view' => [
                    'url' => route('admin.prestasi-pelanggaran.pelanggaran-guru.show', $row),
                    'modal_target' => 'pelanggaran-guru-detail-modal',
                ],
                'delete' => [
                    'url' => route('admin.prestasi-pelanggaran.pelanggaran-guru.destroy', $row),
                    'confirm_title' => 'Hapus Pelanggaran',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus pelanggaran ini?',
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

    public function store(StorePelanggaranGuruRequest $request): JsonResponse
    {
        $sekolahId = AdminSchoolScope::resolveForStore($request);

        $record = DB::transaction(function () use ($request, $sekolahId) {
            $record = PelanggaranGuru::create([
                'guru_id' => $request->integer('guru_id'),
                'sekolah_id' => $sekolahId,
                'jenis_pelanggaran_id' => $request->filled('jenis_pelanggaran_id') ? $request->integer('jenis_pelanggaran_id') : null,
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

        return $this->jsonSuccess(ActionMessage::withSubject('Pelanggaran guru ditambahkan', $subject), $record);
    }

    public function show(PelanggaranGuru $pelanggaranGuru): JsonResponse
    {
        $this->authorize('pelanggaran-guru.view');

        $pelanggaranGuru->load(['guru.sekolah', 'sekolah', 'reportedBy', 'buktiCatatan', 'jenisPelanggaran']);

        return $this->jsonSuccess('OK', [
            'id' => $pelanggaranGuru->id,
            'guru_id' => $pelanggaranGuru->guru_id,
            'guru_name' => $pelanggaranGuru->guru?->name,
            'guru_nip' => $pelanggaranGuru->guru?->nip,
            'guru_jabatan' => $pelanggaranGuru->guru?->jabatan,
            'sekolah_name' => $pelanggaranGuru->sekolah?->name,
            'judul' => $pelanggaranGuru->judul,
            'keterangan' => $pelanggaranGuru->keterangan,
            'tanggal' => $pelanggaranGuru->tanggal->format('Y-m-d'),
            'point' => $pelanggaranGuru->point,
            'jenis_pelanggaran_id' => $pelanggaranGuru->jenis_pelanggaran_id,
            'jenis_nama' => $pelanggaranGuru->jenisPelanggaran?->nama,
            'jenis_level' => $pelanggaranGuru->jenisPelanggaran?->levelLabel(),
            'jenis_sanction' => $pelanggaranGuru->jenisPelanggaran?->sanctionLabel(),
            'jenis_point' => $pelanggaranGuru->jenisPelanggaran?->point,
            'reported_by_name' => $pelanggaranGuru->reportedBy?->name,
            'bukti' => $pelanggaranGuru->buktiCatatan->map(fn ($b) => [
                'id' => $b->id,
                'url' => $b->url,
                'original_name' => $b->original_name,
                'is_image' => $b->isImage(),
                'is_pdf' => $b->isPdf(),
            ]),
        ]);
    }

    public function update(StorePelanggaranGuruRequest $request, PelanggaranGuru $pelanggaranGuru): JsonResponse
    {
        DB::transaction(function () use ($request, $pelanggaranGuru) {
            $pelanggaranGuru->update([
                'guru_id' => $request->integer('guru_id'),
                'jenis_pelanggaran_id' => $request->filled('jenis_pelanggaran_id') ? $request->integer('jenis_pelanggaran_id') : null,
                'judul' => $request->input('judul'),
                'keterangan' => $request->input('keterangan'),
                'tanggal' => $request->input('tanggal'),
                'point' => $request->integer('point'),
            ]);

            $this->handleBuktiUpload($request, $pelanggaranGuru);

            $deletedIds = $request->input('bukti_delete', []);
            if (! empty($deletedIds)) {
                $pelanggaranGuru->buktiCatatan()->whereIn('id', $deletedIds)->each(function (BuktiCatatan $bukti) {
                    Storage::disk('public')->delete($bukti->file_path);
                    $bukti->delete();
                });
            }
        });

        $guru = $pelanggaranGuru->guru;
        $subject = ActionMessage::guru($guru);

        return $this->jsonSuccess(ActionMessage::withSubject('Pelanggaran guru diperbarui', $subject));
    }

    public function destroy(PelanggaranGuru $pelanggaranGuru): JsonResponse
    {
        $this->authorize('pelanggaran-guru.delete');

        $guru = $pelanggaranGuru->guru;
        $subject = ActionMessage::guru($guru);

        DB::transaction(function () use ($pelanggaranGuru) {
            $pelanggaranGuru->buktiCatatan()->each(function (BuktiCatatan $bukti) {
                Storage::disk('public')->delete($bukti->file_path);
                $bukti->delete();
            });
            $pelanggaranGuru->delete();
        });

        return $this->jsonSuccess(ActionMessage::withSubject('Pelanggaran guru dihapus', $subject));
    }

    private function handleBuktiUpload(Request $request, PelanggaranGuru $record): void
    {
        if (! $request->hasFile('bukti')) {
            return;
        }

        $directory = "bukti-catatan/pelanggaran-guru/{$record->id}";

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
                'buktiable_type' => PelanggaranGuru::class,
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
