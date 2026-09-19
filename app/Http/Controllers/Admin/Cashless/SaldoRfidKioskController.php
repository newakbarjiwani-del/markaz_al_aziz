<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\SccttranCashless;
use App\Services\Finance\SccttranCashlessService;
use App\Services\RfidResolver;
use App\Support\ActionMessage;
use App\Support\DisplayDate;
use App\Support\RfidUid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaldoRfidKioskController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private readonly SccttranCashlessService $sccttranCashless,
        private readonly RfidResolver $rfidResolver,
    ) {}

    public function index(): View
    {
        $this->authorize('cashless.view');

        return view('admin.dompet-digital.saldo-rfid', [
            'title' => '',
            // Finance panel is prepared but disabled — do not fetch finance balance yet.
            'financeBalanceEnabled' => false,
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('cashless.view');

        $data = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:64'],
        ]);

        $uid = RfidUid::sanitize($data['rfid_uid']);
        if ($uid === null) {
            return $this->jsonError('UID RFID tidak valid.');
        }

        $siswa = $this->rfidResolver->findSiswa($uid, activeOnly: false);
        $siswa?->loadMissing(['kelas:id,name', 'sekolah:id,name', 'profil:id,siswa_id,photo_path', 'rfid']);

        if (! $siswa) {
            return $this->jsonError('Kartu RFID tidak dikenali.');
        }

        if (! $siswa->canTransact()) {
            return $this->jsonError('Siswa tidak aktif / tidak dapat bertransaksi.');
        }

        $balance = $this->sccttranCashless->balanceForSiswa($siswa->id);
        $recent = SccttranCashless::query()
            ->where('CUSTID', $siswa->id)
            ->orderByDesc('TRXDATE')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'TRXDATE', 'METODE', 'KREDIT', 'DEBET', 'wallet', 'NOREFF']);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Siswa ditemukan', ActionMessage::siswa($siswa)),
            [
                'siswa' => [
                    'id' => $siswa->id,
                    'nis' => $siswa->nis,
                    'name' => $siswa->name,
                    'kelas' => $siswa->kelas?->name,
                    'sekolah' => $siswa->sekolah?->name,
                    'rfid_uid' => $siswa->rfidUid(),
                    'photo_url' => $siswa->profil?->photo_path
                        ? asset('storage/'.$siswa->profil->photo_path)
                        : null,
                ],
                'cashless_balance' => $balance,
                'cashless_balance_formatted' => 'Rp '.number_format($balance, 0, ',', '.'),
                // finance_balance intentionally omitted while financeBalanceEnabled=false
                'recent' => $recent->map(fn (SccttranCashless $row) => [
                    'id' => $row->id,
                    'time' => DisplayDate::time($row->TRXDATE),
                    'date' => DisplayDate::date($row->TRXDATE),
                    'metode' => $row->METODE,
                    'amount' => (int) $row->KREDIT - (int) $row->DEBET,
                    'amount_formatted' => 'Rp '.number_format(abs((int) $row->KREDIT - (int) $row->DEBET), 0, ',', '.'),
                    'direction' => (int) $row->KREDIT > 0 ? 'in' : 'out',
                    'wallet' => $row->wallet,
                    'noreff' => $row->NOREFF,
                ])->values()->all(),
            ]
        );
    }

    public function recent(Request $request): JsonResponse
    {
        $this->authorize('cashless.view');

        $rows = SccttranCashless::query()
            ->with(['siswa:id,name,nis,kelas_id', 'siswa.kelas:id,name'])
            ->whereDate('TRXDATE', now()->toDateString())
            ->orderByDesc('TRXDATE')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return $this->jsonSuccess('OK', [
            'count' => $rows->count(),
            'items' => $rows->map(fn (SccttranCashless $row) => [
                'id' => $row->id,
                'time' => DisplayDate::time($row->TRXDATE),
                'name' => $row->siswa?->name ?? '-',
                'nis' => $row->siswa?->nis ?? '-',
                'kelas' => $row->siswa?->kelas?->name ?? '-',
                'metode' => $row->METODE,
                'amount' => (int) $row->KREDIT - (int) $row->DEBET,
                'amount_formatted' => 'Rp '.number_format(abs((int) $row->KREDIT - (int) $row->DEBET), 0, ',', '.'),
                'direction' => (int) $row->KREDIT > 0 ? 'in' : 'out',
            ])->values()->all(),
        ]);
    }
}
