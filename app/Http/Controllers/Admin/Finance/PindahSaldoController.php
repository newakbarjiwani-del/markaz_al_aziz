<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Siswa;
use App\Services\Finance\PindahSaldoService;
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

    public function __construct(
        private readonly PindahSaldoService $pindahSaldo,
    ) {}

    public function index(): View
    {
        return view('admin.keuangan.pindah-saldo', [
            'title' => 'Pindah Saldo ke Cashless',
            'biayaAdmin' => $this->pindahSaldo->biayaAdmin(),
            'infaqMode' => InfaqTiers::mode(),
            'infaqEnabled' => InfaqTiers::isEnabled(),
            'infaqTiers' => InfaqTiers::allTiers(),
            'infaqMax' => InfaqTiers::max(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'amount' => 'required|numeric|min:1000',
        ];

        if (InfaqTiers::isOptional()) {
            $rules['infaq_amount'] = 'nullable|integer|min:0';
        }

        $data = $request->validate($rules);

        $siswa = Siswa::with('kelas')->findOrFail($data['siswa_id']);
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
                'refno' => $result['refno'],
                'infaq' => $infaq,
            ]
        );
    }
}
