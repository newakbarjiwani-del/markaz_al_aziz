<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\Tagihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagihanController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use PortalAccess;

    public function index(): View
    {
        $siswa = $this->linkedSiswa();

        return view('portal.siswa.tagihan', [
            'title' => 'Tagihan Saya',
            'siswa' => $siswa,
            'stats' => [
                'total' => Tagihan::rootBill()->where('siswa_id', $siswa->id)->count(),
                'lunas' => Tagihan::rootBill()->where('siswa_id', $siswa->id)->paid()->count(),
                'belum_lunas' => Tagihan::rootBill()->where('siswa_id', $siswa->id)->unpaid()->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $siswa = $this->linkedSiswa();
        $virtualAccount = $siswa->virtualAccountNumber() ?? '-';
        $query = Tagihan::query()->rootBill()->where('siswa_id', $siswa->id);
        $this->applyDateRange($query, $request, 'due_date');
        $query->when(
            $request->filled('status') && in_array((string) $request->input('status'), ['0', '1'], true),
            fn ($q) => $q->where('status', (int) $request->input('status'))
        );

        return $this->datatableResponse($request, $query, [
            'searchable' => ['jenis', 'periode'],
            // Keep indexes aligned with displayed columns.
            // Some columns (VA, sisa) are computed, so we map them to safe fallbacks.
            'orderable' => ['created_at', 'jenis', 'periode', 'amount', 'paid', 'amount', 'status', 'paid_dt', 'due_date'],
        ], function (Tagihan $tagihan) use ($virtualAccount) {
            $sisa = $tagihan->remaining();

            return [
                $virtualAccount,
                $tagihan->jenis,
                $tagihan->displayPeriode(),
                $this->cell('Rp '.number_format($tagihan->amount, 0, ',', '.'), (int) $tagihan->amount, 'number'),
                $this->cell('Rp '.number_format($tagihan->paid, 0, ',', '.'), (int) $tagihan->paid, 'number'),
                $this->cell('Rp '.number_format($sisa, 0, ',', '.'), (int) $sisa, 'number'),
                $this->badgeCell($tagihan->statusLabel(), 'badge '.$tagihan->statusBadgeClass()),
                $this->dateCell($tagihan->paid_dt),
                $this->dateCell($tagihan->due_date),
            ];
        });
    }
}
