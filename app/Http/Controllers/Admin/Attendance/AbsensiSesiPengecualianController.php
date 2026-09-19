<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\AbsensiSesiPengecualian;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiSesiPengecualianController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        return view('admin.absensi.sesi-pengecualian.index', [
            'title' => 'Pengecualian Sesi Absensi',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = AbsensiSesiPengecualian::query()
            ->with([
                'jadwalSlot.pelajaran',
                'jadwalSlot.guru',
                'jadwalSlot.hari.jadwalAbsen.sekolah',
                'createdBy',
            ]);

        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['reason'],
            'orderable' => ['date', 'created_at'],
        ], function (AbsensiSesiPengecualian $row) {
            $slot = $row->jadwalSlot;
            $jadwal = $slot?->hari?->jadwalAbsen;
            $pelajaran = $slot?->pelajaran?->name ?? '-';
            $guru = $slot?->guru?->name ?? '-';
            $jadwalName = $jadwal?->name ?? '-';
            $sekolah = $jadwal?->sekolah?->name ?? '-';
            $time = $slot
                ? substr((string) $slot->time_start, 0, 5).'–'.substr((string) $slot->time_end, 0, 5)
                : '-';

            return [
                $row->date,
                $sekolah,
                $jadwalName,
                $pelajaran,
                $guru,
                $time,
                \Illuminate\Support\Str::limit($row->reason, 80),
                $row->createdBy?->name ?? '-',
                $this->cell('', [
                    'actions' => [
                        'delete' => [
                            'url' => route('admin.absensi.sesi-pengecualian.destroy', $row),
                            'confirm_title' => 'Batalkan Pengecualian Sesi',
                            'confirm_message' => 'Sesi absensi ini akan kembali dihitung normal (alpha dapat muncul jika tidak diabsen).',
                            'confirm_detail' => [
                                ['label' => 'Tanggal', 'value' => DisplayDate::date($row->date)],
                                ['label' => 'Pelajaran', 'value' => $pelajaran],
                                ['label' => 'Jadwal', 'value' => $jadwalName],
                                ['label' => 'Alasan', 'value' => $row->reason],
                            ],
                            'confirm_tone' => 'warning',
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function destroy(AbsensiSesiPengecualian $sesiPengecualian): JsonResponse
    {
        $this->authorize('attendance.delete');

        $sesiPengecualian->delete();

        return $this->jsonSuccess('Pengecualian sesi absensi berhasil dibatalkan.');
    }
}
