<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\PotonganPemakaian;
use App\Support\DisplayDate;
use App\Support\PotonganTipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PotonganPemakaianController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        $this->authorize('potongan-tagihan.view');

        return view('admin.keuangan.potongan-pemakaian', [
            'title' => 'Riwayat Pemakaian Potongan',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('potongan-tagihan.view');

        $query = PotonganPemakaian::query()
            ->with([
                'tagihan.siswa.kelas',
                'potonganSiswa.jenisPotongan',
                'user:id,name,username',
            ])
            ->when($request->filled('sekolah_id'), function ($q) use ($request) {
                $q->whereHas('tagihan', fn ($tagihan) => $tagihan->where('sekolah_id', $request->integer('sekolah_id')));
            });

        $this->applyKelasFilter($query, $request, 'tagihan.siswa');
        $this->applyDateRange($query, $request, 'applied_at');

        return $this->datatableResponse($request, $query, [
            'searchable' => [
                'tagihan.jenis',
                'tagihan.siswa.name',
                'tagihan.siswa.nis',
                'potonganSiswa.jenisPotongan.nama',
            ],
            'orderable' => ['applied_at', 'created_at', 'created_at', 'created_at', 'potongan_amount', 'amount_net', 'urutan'],
        ], function (PotonganPemakaian $row) {
            $potongan = (int) round((float) $row->potongan_amount);
            $net = (int) round((float) $row->amount_net);
            $bruto = (int) round((float) $row->amount_bruto);

            return [
                DisplayDate::datetime($row->applied_at),
                $row->tagihan?->siswa?->nis ?? '-',
                $row->tagihan?->siswa?->name ?? '-',
                $row->potonganSiswa?->jenisPotongan?->nama ?? '-',
                $row->tagihan?->jenis ?? '-',
                $row->tagihan?->displayPeriode() ?? '-',
                $this->cell('Rp '.number_format($potongan, 0, ',', '.'), $potongan, 'number'),
                $this->cell('Rp '.number_format($net, 0, ',', '.'), $net, 'number'),
                (string) $row->urutan,
                $row->user?->name ?? 'Otomatis',
            ];
        });
    }
}
