<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Dompet;
use App\Models\Siswa;
use App\Models\TransaksiCashless;
use App\Support\ActionMessage;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransferKantinController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.dompet-digital.transfer-kantin', [
            'title' => 'Transfer US → Kantin',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'amount' => 'required|numeric|min:1000',
        ]);

        $siswa = Siswa::with('kelas')->findOrFail($data['siswa_id']);
        try {
            $siswa->assertCanTransact();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Siswa tidak aktif.',
                $e->errors(),
                422
            );
        }

        $amount = (float) $data['amount'];

        try {
            $dompet = DB::transaction(function () use ($data, $amount) {
                $dompet = Dompet::query()
                    ->where('siswa_id', $data['siswa_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $dompet || (float) $dompet->saldo_us < $amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'Saldo uang saku tidak mencukupi.',
                    ]);
                }

                $dompet->decrement('saldo_us', $amount);
                $dompet->increment('saldo_kantin', $amount);

                TransaksiCashless::create([
                    'siswa_id' => $data['siswa_id'],
                    'type' => 'transfer',
                    'amount' => $amount,
                    'wallet' => 'kantin',
                    'from_wallet' => 'us',
                    'to_wallet' => 'kantin',
                    'description' => 'Transfer US ke dompet kantin',
                ]);

                return $dompet->fresh();
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Transfer gagal.',
                $e->errors(),
                422
            );
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Transfer uang saku ke dompet kantin '.ActionMessage::rupiah($amount).' berhasil',
                ActionMessage::siswa($siswa)
            ),
            $dompet,
            201
        );
    }

    public function data(Request $request): JsonResponse
    {
        $query = TransaksiCashless::query()
            ->with('siswa')
            ->where('type', 'transfer');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['description'],
            'orderable' => ['amount', 'created_at'],
        ], function (TransaksiCashless $row) {
            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                'Rp '.number_format($row->amount, 0, ',', '.'),
                strtoupper($row->from_wallet ?? 'us').' → '.strtoupper($row->to_wallet ?? 'kantin'),
                $row->created_at,
            ];
        });
    }
}
