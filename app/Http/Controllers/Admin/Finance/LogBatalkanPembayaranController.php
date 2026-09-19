<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\FormatsPaymentReceipts;
use App\Models\LogPembayaranBatal;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogBatalkanPembayaranController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use FormatsPaymentReceipts;

    public function index(): View
    {
        $this->authorize('finance.view');

        $baseQuery = LogPembayaranBatal::query();

        return view('admin.keuangan.log-batalkan-pembayaran', [
            'title' => 'Log Batalkan Pembayaran',
            'classes' => $this->classesList(),
            'stats' => [
                'hari_ini' => (clone $baseQuery)->whereDate('created_at', today())->count(),
                'bulan_ini' => (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count(),
                'total' => (clone $baseQuery)->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('finance.view');

        $query = LogPembayaranBatal::query()
            ->with(['siswa.kelas', 'cancelledByUser', 'originalUser'])
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('reference'), fn ($q) => $q->where('reference', 'like', '%'.$request->reference.'%'));

        $this->applyDateRange($query, $request, 'created_at');
        $this->applyKelasFilter($query, $request, 'siswa');
        $this->applySiswaSearchFilter($query, $request, 'siswa');

        return $this->datatableResponse($request, $query->orderByDesc('created_at'), [
            'searchable' => ['reference', 'method', 'ip_address', 'siswa.name', 'siswa.nis', 'cancelledByUser.name', 'cancelledByUser.username'],
            'orderable' => ['created_at', 'reference', 'total_amount'],
        ], function (LogPembayaranBatal $log) {
            $cancelledBy = $log->cancelledByUser
                ? e($log->cancelledByUser->username).' · '.e($log->cancelledByUser->name)
                : '-';

            return [
                $this->dateCell($log->created_at),
                e($log->siswa?->nis ?? '-'),
                e($log->siswa?->name ?? '-'),
                e($log->siswa?->kelas?->name ?? '-'),
                e($log->reference ?? '-'),
                $this->paymentMethodLabel($log->method),
                $this->dateCell($log->paid_at),
                'Rp '.number_format((float) $log->total_amount, 0, ',', '.'),
                $cancelledBy,
                $this->cell(
                    '<button type="button" class="btn-action btn-action-view" data-payment-cancel-log-detail data-log-id="'.$log->id.'" title="Detail" aria-label="Detail"><i class="ti ti-eye"></i><span class="btn-action-label">Detail</span></button>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function show(LogPembayaranBatal $logPembayaranBatal): JsonResponse
    {
        $this->authorize('finance.view');

        return $this->jsonSuccess('Detail log pembatalan.', $this->detailPayload($logPembayaranBatal));
    }

    /** @return array<string, mixed> */
    private function detailPayload(LogPembayaranBatal $log): array
    {
        $log->loadMissing(['siswa.kelas', 'cancelledByUser', 'originalUser']);

        $items = collect($log->items ?? [])->map(function (array $item) {
            $amount = (float) ($item['amount'] ?? 0);
            $billAmount = (float) ($item['bill_amount'] ?? $amount);
            $paidTotal = (float) ($item['paid_total'] ?? $amount);

            return [
                'jenis' => $item['jenis'] ?? '-',
                'periode' => $item['periode'] ?? '-',
                'amount' => $amount,
                'amount_label' => 'Rp '.number_format($amount, 0, ',', '.'),
                'bill_amount' => $billAmount,
                'bill_amount_label' => 'Rp '.number_format($billAmount, 0, ',', '.'),
                'paid_total' => $paidTotal,
                'paid_total_label' => 'Rp '.number_format($paidTotal, 0, ',', '.'),
                'is_cicilan' => (bool) ($item['is_cicilan'] ?? false),
            ];
        })->values()->all();

        return [
            'id' => $log->id,
            'created_at' => DisplayDate::longDatetime($log->created_at),
            'pembayaran_id' => $log->pembayaran_id,
            'reference' => $log->reference,
            'method' => $log->method,
            'method_label' => $this->paymentMethodLabel($log->method),
            'paid_at' => DisplayDate::longDatetime($log->paid_at),
            'total_amount' => (float) $log->total_amount,
            'total_label' => 'Rp '.number_format((float) $log->total_amount, 0, ',', '.'),
            'siswa' => [
                'nis' => $log->siswa?->nis,
                'name' => $log->siswa?->name,
                'kelas' => $log->siswa?->kelas?->name,
            ],
            'cancelled_by' => [
                'username' => $log->cancelledByUser?->username,
                'name' => $log->cancelledByUser?->name,
            ],
            'original_operator' => [
                'username' => $log->originalUser?->username,
                'name' => $log->originalUser?->name,
            ],
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'items' => $items,
        ];
    }
}
