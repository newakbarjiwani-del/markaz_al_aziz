<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\FormatsPaymentReceipts;
use App\Models\Pembayaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiwayatPembayaranController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use FormatsPaymentReceipts;

    public function index(): View
    {
        return view('admin.keuangan.riwayat-pembayaran', [
            'title' => 'Riwayat Pembayaran',
            'classes' => $this->classesList(),
            'stats' => [
                'hari_ini' => Pembayaran::whereDate('paid_dt', today())->sum('total_amount'),
                'bulan_ini' => Pembayaran::where('paid_dt', '>=', now()->startOfMonth())->sum('total_amount'),
                'total_kuitansi' => Pembayaran::count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Pembayaran::query()
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('reference'), fn ($q) => $q->where('reference', 'like', '%'.$request->reference.'%'));

        $this->applyDateRange($query, $request, 'paid_dt');
        $this->applyKelasFilter($query, $request, 'siswa');
        $this->applySiswaSearchFilter($query, $request, 'siswa');

        return $this->paymentReceiptDatatableResponse($request, $query);
    }

    public function receipt(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1'],
            'ids.*' => ['integer'],
        ]);

        $payment = Pembayaran::query()
            ->with(['user', 'details.tagihan', 'siswa.kelas.sekolah'])
            ->find($data['ids'][0]);

        if ($payment === null) {
            return $this->jsonError('Kuitansi tidak ditemukan.');
        }

        return $this->jsonSuccess('OK', $this->formatPaymentReceipt($payment));
    }
}
