<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\AbsensiSiswa;
use App\Models\JadwalAbsen;
use App\Models\Pelajaran;
use App\Support\AdminSchoolScope;
use App\Support\AttendanceStatus;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiSiswaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        $jadwals = JadwalAbsen::query();
        AdminSchoolScope::applyWithGlobal($jadwals);

        $pelajarans = Pelajaran::query();
        AdminSchoolScope::applyWithGlobal($pelajarans);

        return view('admin.absensi.absensi-siswa', [
            'title' => 'Absensi Siswa',
            'schools' => AdminSchoolScope::schools(),
            'classes' => $this->classesList(),
            'jadwals' => $jadwals->orderBy('name')->get(),
            'pelajarans' => $pelajarans->orderBy('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = AbsensiSiswa::query()->with([
            'siswa.kelas',
            'jadwalSlot.pelajaran',
            'jadwalSlot.hari.jadwalAbsen',
        ]);

        if ($request->filled('sekolah_id')) {
            $query->where('sekolah_id', $request->sekolah_id);
        }

        $this->applyKelasFilter($query, $request);

        if ($request->filled('jadwal_absen_id')) {
            $query->whereHas('jadwalSlot.hari', function (Builder $q) use ($request) {
                $q->where('jadwal_absen_id', $request->jadwal_absen_id);
            });
        }

        if ($request->filled('pelajaran_id')) {
            $query->whereHas('jadwalSlot', function (Builder $q) use ($request) {
                $q->where('pelajaran_id', $request->pelajaran_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => [
                'status',
                'method',
                'siswa.nis',
                'siswa.name',
                'siswa.kelas.name',
                'jadwalSlot.pelajaran.name',
                'jadwalSlot.hari.jadwalAbsen.name',
            ],
            'orderable' => ['date', 'status', 'created_at'],
        ], function (AbsensiSiswa $row) {
            $jadwalName = $row->jadwalSlot?->hari?->jadwalAbsen?->name ?? '-';
            $pelajaranName = $row->jadwalSlot?->pelajaran?->name ?? '-';
            $slotRange = trim(
                DisplayDate::time($row->jadwalSlot?->time_start).' - '.DisplayDate::time($row->jadwalSlot?->time_end)
            );
            $jadwalLabel = $jadwalName !== '-'
                ? $jadwalName.($slotRange !== '- -' ? ' ('.$slotRange.')' : '')
                : '-';

            $timeIn = DisplayDate::time($row->time_in);
            $method = strtoupper((string) ($row->method ?? ''));
            $jamDanMetode = $timeIn !== '-'
                ? ($method !== '' && $method !== '-' ? $timeIn.' ('.$method.')' : $timeIn)
                : ($method !== '' && $method !== '-' ? $method : '-');

            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $jadwalLabel,
                $pelajaranName,
                $row->date,
                AttendanceStatus::label($row->status),
                $jamDanMetode,
            ];
        });
    }
}

