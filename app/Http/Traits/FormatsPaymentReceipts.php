<?php

namespace App\Http\Traits;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\Finance\PembayaranCancellationService;
use App\Support\DisplayDate;
use App\Support\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait FormatsPaymentReceipts
{
    /**
     * @return array<string, mixed>
     */
    protected function formatPaymentReceipt(Pembayaran $payment): array
    {
        $payment->loadMissing(['user', 'details.tagihan', 'siswa.kelas.sekolah']);
        $siswa = $payment->siswa;
        $details = $payment->details->sortBy('id')->values();

        return [
            'receipt_key' => (string) $payment->id,
            'payment_id' => $payment->id,
            'reference' => $payment->reference,
            'method' => $payment->method,
            'method_label' => $this->paymentMethodLabel($payment->method),
            'operator' => $this->paymentOperatorName($payment),
            'paid_dt' => DisplayDate::longDatetime($payment->paid_dt),
            'paid_dt_sort' => $payment->paid_dt?->format('Y-m-d H:i:s'),
            'total_amount' => (float) $payment->total_amount,
            'total_label' => 'Rp '.number_format((float) $payment->total_amount, 0, ',', '.'),
            'item_count' => $details->count(),
            'payment_ids' => [$payment->id],
            'siswa' => [
                'nis' => $siswa?->nis,
                'name' => $siswa?->name,
                'kelas' => $siswa?->kelas?->name,
                'virtual_account' => $siswa?->virtualAccountNumber(),
            ],
            'school' => [
                'name' => $siswa?->sekolah?->name ?? config('app.name'),
            ],
            'items' => $details->map(function ($detail) use ($payment) {
                $tagihan = $detail->tagihan;
                $currentPaid = (float) $detail->amount;
                $billAmount = $tagihan?->amount_bruto !== null
                    ? (float) $tagihan->amount_bruto
                    : ($tagihan?->displayAmount() ?? $currentPaid);
                $potonganAmount = (float) ($tagihan?->potongan_amount ?? 0);
                $paidTotal = $this->receiptPaidTotal($tagihan, $payment, $currentPaid);

                return [
                    'jenis' => $tagihan?->jenis,
                    'periode' => $tagihan?->displayPeriode(),
                    'amount' => $currentPaid,
                    'amount_label' => 'Rp '.number_format($currentPaid, 0, ',', '.'),
                    'bill_amount' => $billAmount,
                    'bill_amount_label' => 'Rp '.number_format($billAmount, 0, ',', '.'),
                    'potongan_amount' => $potonganAmount,
                    'potongan_amount_label' => $potonganAmount > 0
                        ? 'Rp '.number_format($potonganAmount, 0, ',', '.')
                        : '-',
                    'paid_total' => $paidTotal,
                    'paid_total_label' => 'Rp '.number_format($paidTotal, 0, ',', '.'),
                    'is_cicilan' => (bool) ($tagihan?->isInstallmentParent()),
                ];
            })->values()->all(),
        ];
    }

    protected function receiptPaidTotal(?Tagihan $tagihan, Pembayaran $payment, float $currentPaid): float
    {
        if ($tagihan === null) {
            return $currentPaid;
        }

        if ($tagihan->isInstallmentParent()) {
            $childQuery = $tagihan->cicilanChildren()
                ->where('reference', $payment->reference)
                ->where('amount', $currentPaid);

            if ($payment->paid_dt !== null) {
                $childQuery->where('paid_dt', $payment->paid_dt);
            }

            $child = $childQuery->orderByDesc('cicilan_ke')->first();

            if ($child !== null) {
                return (float) $tagihan->cicilanChildren()
                    ->where('cicilan_ke', '<=', $child->cicilan_ke)
                    ->sum('amount');
            }

            return max((float) $tagihan->paid, $currentPaid);
        }

        return max((float) $tagihan->paid, $currentPaid);
    }

    protected function paymentOperatorName(Pembayaran $payment): string
    {
        $recorded = trim((string) ($payment->user?->name ?: $payment->user?->username ?: ''));
        if ($recorded !== '') {
            return $recorded;
        }

        $current = trim((string) (auth()->user()?->name ?: auth()->user()?->username ?: ''));
        if ($current !== '') {
            return $current;
        }

        return $payment->method === 'va' ? 'Virtual Account' : '-';
    }

    protected function paymentMethodLabel(?string $method): string
    {
        return PaymentMethod::label($method);
    }

    /**
     * @param  array<string, mixed>  $receipt
     */
    protected function receiptActionCell(array $receipt): array
    {
        $id = (string) ($receipt['payment_id'] ?? $receipt['payment_ids'][0] ?? '');

        $html = '<div class="flex items-center justify-end gap-1">'
            .'<button type="button"'
            .' data-payment-detail="1"'
            .' data-payment-id="'.e($id).'"'
            .' class="btn-action btn-action-view"'
            .' title="Detail Tagihan"'
            .' aria-label="Detail Tagihan">'
            .'<i class="ti ti-eye"></i><span class="btn-action-label">Detail</span></button>'
            .'<button type="button"'
            .' data-print-payment-receipt="1"'
            .' data-payment-ids="'.e($id).'"'
            .' class="btn-action btn-action-edit"'
            .' title="Cetak Kuitansi"'
            .' aria-label="Cetak Kuitansi">'
            .'<i class="ti ti-printer"></i><span class="btn-action-label">Cetak</span></button>'
            .'</div>';

        return $this->cell($html, null, 'action');
    }

    /**
     * @param  array<string, mixed>  $receipt
     */
    protected function cancellationActionCell(array $receipt, Pembayaran $payment): array
    {
        $id = (string) ($receipt['payment_id'] ?? $receipt['payment_ids'][0] ?? '');
        $cancelUrl = route('admin.keuangan.batalkan-pembayaran.destroy', $payment);

        $html = '<div class="flex items-center justify-end gap-1">'
            .'<button type="button"'
            .' data-payment-detail="1"'
            .' data-payment-id="'.e($id).'"'
            .' class="btn-action btn-action-view"'
            .' title="Detail Tagihan"'
            .' aria-label="Detail Tagihan">'
            .'<i class="ti ti-eye"></i><span class="btn-action-label">Detail</span></button>'
            .'<button type="button"'
            .' data-fetch-delete="'.e($cancelUrl).'"'
            .' data-confirm-title="Batalkan Pembayaran"'
            .' data-confirm-message="Pembayaran kasir ini akan dibatalkan."'
            .' data-confirm-detail="'.\App\Support\ConfirmDetail::attr([
                ['label' => 'Referensi', 'value' => (string) ($payment->reference ?? '-')],
                ['label' => 'Dampak', 'value' => 'Tagihan dikembalikan ke belum lunas; cicilan menghapus baris cicilan terkait.'],
            ]).'"'
            .' data-confirm-text="Ya, Batalkan"'
            .' data-confirm-tone="danger"'
            .' data-confirm-icon="ti-receipt-off"'
            .' data-confirm-header-icon="ti-receipt-off"'
            .' data-confirm-footnote="Pembatalan tercatat di log. Saldo keuangan dikembalikan jika metode bayar dari saldo."'
            .' class="btn-action btn-action-delete"'
            .' title="Batalkan Pembayaran"'
            .' aria-label="Batalkan Pembayaran">'
            .'<i class="ti ti-x"></i><span class="btn-action-label">Batalkan</span></button>'
            .'</div>';

        return $this->cell($html, null, 'action');
    }

    protected function paymentCancellationDatatableResponse(
        Request $request,
        Builder $query,
        PembayaranCancellationService $cancellationService,
    ): JsonResponse {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(10, (int) $request->input('length', 25)));

        $recordsTotal = (clone $query)->count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $query->where(function (Builder $builder) use ($needle) {
                $builder
                    ->where('reference', 'like', '%'.$needle.'%')
                    ->orWhereHas('siswa', function (Builder $siswaQuery) use ($needle) {
                        $siswaQuery
                            ->where('name', 'like', '%'.$needle.'%')
                            ->orWhere('nis', 'like', '%'.$needle.'%')
                            ->orWhereHas('kelas', fn (Builder $kelasQuery) => $kelasQuery->where('name', 'like', '%'.$needle.'%'));
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $payments = (clone $query)
            ->with(['user', 'details.tagihan', 'siswa.kelas.sekolah'])
            ->orderByDesc('paid_dt')
            ->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $payments->map(function (Pembayaran $payment) use ($cancellationService) {
            if (! $cancellationService->canCancel($payment)) {
                return null;
            }

            $receipt = $this->formatPaymentReceipt($payment);

            return [
                $receipt['paid_dt'],
                $receipt['siswa']['nis'] ?? '-',
                $receipt['siswa']['name'] ?? '-',
                $receipt['siswa']['kelas'] ?? '-',
                $receipt['reference'] ?? '-',
                $receipt['method_label'],
                $receipt['item_count'],
                $receipt['total_label'],
                $this->cancellationActionCell($receipt, $payment),
            ];
        })->filter()->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    protected function paymentReceiptDatatableResponse(Request $request, Builder $query): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(10, (int) $request->input('length', 25)));

        $recordsTotal = (clone $query)->count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $query->where(function (Builder $builder) use ($needle) {
                $builder
                    ->where('reference', 'like', '%'.$needle.'%')
                    ->orWhereHas('siswa', function (Builder $siswaQuery) use ($needle) {
                        $siswaQuery
                            ->where('name', 'like', '%'.$needle.'%')
                            ->orWhere('nis', 'like', '%'.$needle.'%')
                            ->orWhereHas('kelas', fn (Builder $kelasQuery) => $kelasQuery->where('name', 'like', '%'.$needle.'%'));
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $payments = (clone $query)
            ->with(['user', 'details.tagihan', 'siswa.kelas.sekolah'])
            ->orderByDesc('paid_dt')
            ->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $payments->map(function (Pembayaran $payment) {
            $receipt = $this->formatPaymentReceipt($payment);

            return [
                $receipt['paid_dt'],
                $receipt['siswa']['nis'] ?? '-',
                $receipt['siswa']['name'] ?? '-',
                $receipt['siswa']['kelas'] ?? '-',
                $receipt['reference'] ?? '-',
                $receipt['method_label'],
                $receipt['item_count'],
                $receipt['total_label'],
                $this->receiptActionCell($receipt),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
