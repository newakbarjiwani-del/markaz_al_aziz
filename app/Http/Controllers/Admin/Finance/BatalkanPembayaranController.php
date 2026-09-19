<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\FormatsPaymentReceipts;
use App\Models\Pembayaran;
use App\Services\Finance\PembayaranCancellationService;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BatalkanPembayaranController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use FormatsPaymentReceipts;

    public function index(): View
    {
        $this->authorize('finance.view');

        $baseQuery = Pembayaran::query()
            ->whereIn('method', PembayaranCancellationService::CANCELLABLE_METHODS);

        return view('admin.keuangan.batalkan-pembayaran', [
            'title' => 'Batalkan Pembayaran',
            'classes' => $this->classesList(),
            'stats' => [
                'hari_ini' => (clone $baseQuery)->whereDate('paid_dt', today())->sum('total_amount'),
                'bulan_ini' => (clone $baseQuery)->where('paid_dt', '>=', now()->startOfMonth())->sum('total_amount'),
                'total_kuitansi' => (clone $baseQuery)->count(),
            ],
        ]);
    }

    public function data(Request $request, PembayaranCancellationService $cancellationService): JsonResponse
    {
        $this->authorize('finance.view');

        $query = Pembayaran::query()
            ->whereIn('method', PembayaranCancellationService::CANCELLABLE_METHODS)
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('reference'), fn ($q) => $q->where('reference', 'like', '%'.$request->reference.'%'));

        $this->applyDateRange($query, $request, 'paid_dt');
        $this->applyKelasFilter($query, $request, 'siswa');
        $this->applySiswaSearchFilter($query, $request, 'siswa');

        return $this->paymentCancellationDatatableResponse($request, $query, $cancellationService);
    }

    public function receipt(Request $request): JsonResponse
    {
        $this->authorize('finance.view');

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1'],
            'ids.*' => ['integer'],
        ]);

        $payment = Pembayaran::query()
            ->whereIn('method', PembayaranCancellationService::CANCELLABLE_METHODS)
            ->with(['user', 'details.tagihan', 'siswa.kelas.sekolah'])
            ->find($data['ids'][0]);

        if ($payment === null) {
            return $this->jsonError('Pembayaran tidak ditemukan.');
        }

        return $this->jsonSuccess('OK', $this->formatPaymentReceipt($payment));
    }

    public function destroy(Request $request, Pembayaran $pembayaran, PembayaranCancellationService $cancellationService): JsonResponse
    {
        $this->authorize('finance.delete');

        try {
            $payment = $cancellationService->cancel($pembayaran, $request);
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Pembayaran gagal dibatalkan.',
                $e->errors(),
                422
            );
        }

        $payment->load(['siswa.kelas']);

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Pembayaran berhasil dibatalkan',
                ($payment->siswa?->name ?? '-').' · '.$payment->reference
            )
        );
    }
}
