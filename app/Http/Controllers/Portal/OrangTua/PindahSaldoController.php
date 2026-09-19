<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\PortalAccess;
use App\Models\Siswa;
use App\Services\Finance\PindahSaldoService;
use App\Services\Finance\SccttranCashlessService;
use App\Services\Finance\SccttranSaldoService;
use App\Support\ActionMessage;
use App\Support\InfaqTiers;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PindahSaldoController extends Controller
{
    use DataTableTrait;
    use PortalAccess;

    public function __construct(
        private readonly PindahSaldoService $pindahSaldo,
        private readonly SccttranSaldoService $sccttranSaldo,
        private readonly SccttranCashlessService $sccttranCashless,
    ) {}

    public function index(): View
    {
        $children = $this->ortuChildren();

        $childIds = $children->pluck('id')->all();

        return view('portal.ortu.pindah-saldo', [
            'title' => 'Pindah Saldo ke Cashless',
            'children' => $children,
            'biayaAdmin' => $this->pindahSaldo->biayaAdmin(),
            'balancesKeuangan' => collect($this->sccttranSaldo->balancesForSiswaIds($childIds)),
            'balancesCashless' => collect($this->sccttranCashless->balancesForSiswaIds($childIds)),
            'infaqMode' => InfaqTiers::mode(),
            'infaqEnabled' => InfaqTiers::isEnabled(),
            'infaqTiers' => InfaqTiers::allTiers(),
            'infaqMax' => InfaqTiers::max(),
        ]);
    }

    public function saldo(Request $request): JsonResponse
    {
        $siswa = $this->resolveLinkedChild($request);

        return $this->jsonSuccess('OK', [
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name,
            ],
            'balance' => $this->sccttranSaldo->balanceForSiswa($siswa->id),
            'cashless_balance' => $this->sccttranCashless->balanceForSiswa($siswa->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $siswa = $this->resolveLinkedChild($request);

        $rules = [
            'amount' => 'required|numeric|min:1000',
        ];

        if (InfaqTiers::isOptional()) {
            $rules['infaq_amount'] = 'nullable|integer|min:0';
        }

        $data = $request->validate($rules);
        $amount = (int) round($data['amount']);

        $infaqOverride = InfaqTiers::isOptional()
            ? (isset($data['infaq_amount']) ? (int) $data['infaq_amount'] : 0)
            : null;

        try {
            $result = $this->pindahSaldo->transfer($siswa, $amount, auth()->id(), $infaqOverride);
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Pindah saldo gagal.',
                $e->errors(),
                422
            );
        }

        $biayaAdmin = $result['biaya_admin'];
        $infaq = $result['infaq'];

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Pindah saldo '.ActionMessage::rupiah($amount).' ke cashless berhasil'
                .($biayaAdmin > 0 ? ' (biaya admin Rp '.number_format($biayaAdmin, 0, ',', '.').')' : '')
                .($infaq > 0 ? ' (infaq Rp '.number_format($infaq, 0, ',', '.').')' : ''),
                ActionMessage::siswa($siswa)
            ),
            [
                'saldo_keuangan' => $result['saldo_keuangan'],
                'saldo_cashless' => $this->sccttranCashless->balanceForSiswa($siswa->id),
                'refno' => $result['refno'],
                'infaq' => $infaq,
            ]
        );
    }

    private function resolveLinkedChild(Request $request): Siswa
    {
        $linkedIds = $this->linkedOrangTua()
            ->siswa()
            ->pluck('siswa.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_if($linkedIds === [], 403, 'Tidak ada data anak yang terhubung.');

        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
        ]);

        $siswaId = (int) $data['siswa_id'];
        abort_unless(in_array($siswaId, $linkedIds, true), 403, 'Anak tidak terhubung ke akun ini.');

        return Siswa::with('kelas')->findOrFail($siswaId);
    }
}
