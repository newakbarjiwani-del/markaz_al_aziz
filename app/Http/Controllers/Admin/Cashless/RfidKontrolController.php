<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Rfid;
use App\Models\Siswa;
use App\Support\ActionMessage;
use App\Support\RfidUid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RfidKontrolController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.dompet-digital.rfid-kontrol', [
            'title' => 'Kontrol RFID',
            'stats' => [
                'aktif' => Rfid::query()->whereNotNull('siswa_id')->where('blocked', false)->count(),
                'diblokir' => Rfid::query()->whereNotNull('siswa_id')->where('blocked', true)->count(),
                'belum_kartu' => Siswa::query()->whereDoesntHave('rfid')->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Siswa::query()
            ->leftJoin('rfid', 'rfid.siswa_id', '=', 'siswa.id')
            ->select('siswa.*')
            ->with(['kelas', 'rfid'])
            ->when($request->filled('rfid_blocked'), function ($q) use ($request) {
                $q->where('rfid.blocked', $request->boolean('rfid_blocked'));
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['siswa.nis', 'siswa.name', 'rfid.uid'],
            'orderable' => ['siswa.nis', 'siswa.name', 'rfid.uid', 'siswa.created_at'],
        ], function (Siswa $siswa) {
            $rfidDisplay = filled($siswa->rfidUid())
                ? $this->cell('<span class="font-mono text-sm text-slate-800 dark:text-slate-200">'.e($siswa->rfidUid()).'</span>', $siswa->rfidUid(), 'text')
                : $this->cell('<span class="text-muted text-sm">Belum terdaftar</span>', '-', 'text');

            $pinSet = filled($siswa->cashless_pin);
            $pinCell = $pinSet
                ? $this->cell('<span class="badge badge-success">Aktif</span>', 1, 'text')
                : $this->cell('<span class="text-muted">Belum</span>', 0, 'text');

            return [
                $siswa->nis,
                $siswa->name,
                $siswa->kelas?->name ?? '-',
                $rfidDisplay,
                $this->cell(view('admin.dompet-digital.partials.rfid-card-status', ['siswa' => $siswa])->render(), null, 'text'),
                $pinCell,
                $this->cell(view('admin.dompet-digital.partials.rfid-row-actions', ['siswa' => $siswa])->render(), null, 'action'),
            ];
        });
    }

    public function updateRfid(Request $request, Siswa $siswa): JsonResponse
    {
        $request->merge([
            'rfid_uid' => RfidUid::sanitize($request->input('rfid_uid')),
        ]);

        $data = $request->validate(
            ['rfid_uid' => RfidUid::rules($siswa->id, required: true)],
            RfidUid::messages()
        );

        $siswa->assignRfid($data['rfid_uid'], $siswa->isRfidBlocked());
        $siswa->load('kelas');

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'RFID cashless berhasil diperbarui',
                ActionMessage::siswa($siswa)
            ),
            $siswa->fresh(['kelas'])
        );
    }

    public function updateBlock(Request $request, Siswa $siswa): JsonResponse
    {
        $data = $request->validate([
            'rfid_blocked' => ['required', 'boolean'],
        ]);

        $rfid = $siswa->rfid;
        if (! $rfid) {
            return $this->jsonError('Pasang RFID terlebih dahulu.');
        }

        $rfid->update(['blocked' => $data['rfid_blocked']]);
        $siswa->load('kelas');
        $action = $data['rfid_blocked']
            ? 'RFID cashless berhasil diblokir'
            : 'RFID cashless berhasil diaktifkan kembali';

        return $this->jsonSuccess(
            ActionMessage::withSubject($action, ActionMessage::siswa($siswa)),
            $siswa->fresh(['kelas'])
        );
    }
}
