<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AssignJadwalAbsensiGuruRequest;
use App\Http\Requests\Attendance\StoreJadwalAbsensiGuruRequest;
use App\Http\Requests\Attendance\UpdateJadwalAbsensiGuruRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Guru;
use App\Models\JadwalAbsensiGuru;
use App\Models\Sekolah;
use App\Support\AdminSchoolScope;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JadwalAbsensiGuruController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.absensi.jadwal-absensi-guru.index', [
            'title' => 'Jadwal Absensi Guru',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = JadwalAbsensiGuru::query()
            ->with(['sekolah'])
            ->withCount('gurus')
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->sekolah_id))
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name'],
            'orderable' => ['name', 'jam_masuk', 'jam_pulang', 'created_at'],
        ], function (JadwalAbsensiGuru $row) {
            return [
                $row->sekolah?->name ?? '-',
                $row->name,
                DisplayDate::time($row->jam_masuk),
                DisplayDate::time($row->jam_pulang),
                $row->toleransi_menit.' menit',
                (string) $row->gurus_count,
                $this->badgeCell($row->is_active ? 'Aktif' : 'Nonaktif', $row->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell(view('admin.absensi.jadwal-absensi-guru.partials.row-actions', ['jadwal' => $row])->render(), null, 'action'),
            ];
        });
    }

    public function store(StoreJadwalAbsensiGuruRequest $request): JsonResponse
    {
        $this->authorize('attendance.create');

        $jadwal = JadwalAbsensiGuru::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess('Jadwal absensi guru berhasil ditambahkan.', $jadwal, 201);
    }

    public function update(UpdateJadwalAbsensiGuruRequest $request, JadwalAbsensiGuru $jadwalAbsensiGuru): JsonResponse
    {
        $this->authorize('attendance.update');

        $data = $request->validated();
        $sekolahChanged = (int) $data['sekolah_id'] !== (int) $jadwalAbsensiGuru->sekolah_id;

        DB::transaction(function () use ($request, $jadwalAbsensiGuru, $data, $sekolahChanged): void {
            $jadwalAbsensiGuru->update([
                ...$data,
                'is_active' => $request->boolean('is_active'),
            ]);

            if ($sekolahChanged) {
                Guru::query()
                    ->where('jadwal_absensi_guru_id', $jadwalAbsensiGuru->id)
                    ->update(['jadwal_absensi_guru_id' => null]);
            }
        });

        return $this->jsonSuccess('Jadwal absensi guru berhasil diperbarui.', $jadwalAbsensiGuru);
    }

    public function destroy(JadwalAbsensiGuru $jadwalAbsensiGuru): JsonResponse
    {
        $this->authorize('attendance.delete');

        if ($jadwalAbsensiGuru->gurus()->exists()) {
            return $this->jsonError('Jadwal masih digunakan guru. Pindahkan guru terlebih dahulu atau hapus penugasan.');
        }

        $jadwalAbsensiGuru->delete();

        return $this->jsonSuccess('Jadwal absensi guru berhasil dihapus.');
    }

    public function gurus(JadwalAbsensiGuru $jadwalAbsensiGuru): JsonResponse
    {
        $gurus = Guru::query()
            ->where('sekolah_id', $jadwalAbsensiGuru->sekolah_id)
            ->orderBy('name')
            ->get(['id', 'nip', 'name', 'jabatan', 'jadwal_absensi_guru_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'jadwal' => [
                    'id' => $jadwalAbsensiGuru->id,
                    'name' => $jadwalAbsensiGuru->name,
                    'sekolah' => $jadwalAbsensiGuru->sekolah?->name,
                ],
                'gurus' => $gurus->map(fn (Guru $guru) => [
                    'id' => $guru->id,
                    'nip' => $guru->nip,
                    'name' => $guru->name,
                    'jabatan' => $guru->jabatan,
                    'assigned' => (int) $guru->jadwal_absensi_guru_id === (int) $jadwalAbsensiGuru->id,
                    'other_jadwal' => $guru->jadwal_absensi_guru_id
                        && (int) $guru->jadwal_absensi_guru_id !== (int) $jadwalAbsensiGuru->id,
                ]),
            ],
        ]);
    }

    public function assignGurus(AssignJadwalAbsensiGuruRequest $request, JadwalAbsensiGuru $jadwalAbsensiGuru): JsonResponse
    {
        $this->authorize('attendance.update');

        $guruIds = collect($request->input('guru_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $validIds = Guru::query()
            ->where('sekolah_id', $jadwalAbsensiGuru->sekolah_id)
            ->whereIn('id', $guruIds)
            ->pluck('id');

        if ($validIds->count() !== $guruIds->count()) {
            return $this->jsonError('Daftar guru tidak valid untuk sekolah jadwal ini.');
        }

        DB::transaction(function () use ($jadwalAbsensiGuru, $validIds): void {
            Guru::query()
                ->where('jadwal_absensi_guru_id', $jadwalAbsensiGuru->id)
                ->whereNotIn('id', $validIds)
                ->update(['jadwal_absensi_guru_id' => null]);

            Guru::query()
                ->whereIn('id', $validIds)
                ->update(['jadwal_absensi_guru_id' => $jadwalAbsensiGuru->id]);
        });

        return $this->jsonSuccess('Guru berhasil ditugaskan ke jadwal ini.');
    }
}
