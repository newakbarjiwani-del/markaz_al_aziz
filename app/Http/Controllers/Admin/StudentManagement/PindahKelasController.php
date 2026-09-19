<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Kelas;
use App\Models\PindahKelas;
use App\Models\Siswa;
use App\Support\ActionMessage;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PindahKelasController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.manajemen-siswa.pindah-kelas', [
            'title' => 'Pindah Kelas',
            'classes' => Kelas::orderBy('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PindahKelas::query()->with(['siswa', 'dariKelas', 'keKelas']);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status'],
            'orderable' => ['status', 'created_at'],
        ], function (PindahKelas $row) {
            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->dariKelas?->name ?? '-',
                $row->keKelas?->name ?? '-',
                ucfirst($row->status),
                $row->created_at,
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'ke_kelas_id' => ['required', SoftDeleteRules::exists('kelas')],
            'notes' => 'nullable|string',
        ]);

        $siswa = Siswa::findOrFail($data['siswa_id']);

        $pindah = DB::transaction(function () use ($siswa, $data) {
            $pindah = PindahKelas::create([
                'siswa_id' => $siswa->id,
                'dari_kelas_id' => $siswa->kelas_id,
                'ke_kelas_id' => $data['ke_kelas_id'],
                'status' => 'selesai',
                'notes' => $data['notes'] ?? null,
            ]);

            $siswa->update(['kelas_id' => $data['ke_kelas_id']]);

            return $pindah;
        });

        $keKelas = Kelas::find($data['ke_kelas_id']);
        $dariKelas = Kelas::find($pindah->dari_kelas_id);
        $detail = ActionMessage::siswa($siswa->fresh('kelas'))
            .' · '.($dariKelas?->name ?? '-')
            .' → '
            .($keKelas?->name ?? '-');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Pindah kelas berhasil diproses', $detail),
            $pindah->load(['siswa', 'dariKelas', 'keKelas']),
            201
        );
    }
}
