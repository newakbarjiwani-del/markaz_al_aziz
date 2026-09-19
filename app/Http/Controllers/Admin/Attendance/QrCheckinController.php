<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\AbsensiSiswa;
use App\Models\HariLibur;
use App\Models\Siswa;
use App\Services\HariLiburService;
use App\Support\ActionMessage;
use App\Support\AttendanceStatus;
use App\Support\DisplayDate;
use App\Support\VirtualAccountNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrCheckinController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private HariLiburService $hariLiburService
    ) {}

    public function index(): View
    {
        return view('admin.absensi.absensi-qr', ['title' => 'QR Code Check-in']);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nis' => VirtualAccountNumber::nisLookupRules(),
            'status' => AttendanceStatus::validationRule(true),
        ]);

        $nis = VirtualAccountNumber::normalizeNis($data['nis']);
        $siswa = Siswa::where('nis', $nis)->first();
        if (! $siswa) {
            return $this->jsonError('Siswa dengan NIS tersebut tidak ditemukan.');
        }

        $today = now()->toDateString();

        try {
            $this->hariLiburService->assertNotLibur($today, $siswa->sekolah_id, HariLibur::APPLIES_SISWA);
        } catch (\RuntimeException $exception) {
            return $this->jsonError($exception->getMessage());
        }

        $existing = AbsensiSiswa::where('siswa_id', $siswa->id)->where('date', $today)->first();
        if ($existing) {
            return $this->jsonError('Siswa sudah melakukan check-in hari ini.');
        }

        AbsensiSiswa::create([
            'sekolah_id' => $siswa->sekolah_id,
            'siswa_id' => $siswa->id,
            'date' => $today,
            'status' => $data['status'],
            'method' => 'qr',
            'time_in' => AttendanceStatus::isAbsent($data['status']) ? null : now()->format('H:i'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Check-in QR berhasil', ActionMessage::siswa($siswa)),
            ['siswa' => $siswa->only(['nis', 'name'])],
            201
        );
    }

    public function data(Request $request): JsonResponse
    {
        $query = AbsensiSiswa::query()
            ->with('siswa')
            ->where('date', now()->toDateString());

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status'],
            'orderable' => ['time_in', 'status', 'created_at'],
        ], function (AbsensiSiswa $row) {
            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                ucfirst($row->status),
                DisplayDate::time($row->time_in),
            ];
        });
    }
}
