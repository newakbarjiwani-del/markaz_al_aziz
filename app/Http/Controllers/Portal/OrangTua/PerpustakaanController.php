<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerpustakaanController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use PortalAccess;

    public function index(): View
    {
        return view('portal.ortu.perpustakaan', [
            'title' => 'Peminjaman Buku Anak',
            'children' => $this->ortuChildren(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Peminjaman::query()
            ->select('peminjaman.*')
            ->with(['items.buku', 'siswa.kelas']);
        $this->applyOrtuSiswaScope($query, $request, 'siswa_id');
        $this->applyDateRange($query, $request, 'loan_date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['borrower_type'],
            'orderable' => ['loan_date', 'created_at', 'borrower_type'],
        ], function (Peminjaman $row) {
            $bookList = $row->items->map(function ($item) {
                $judul = $item->buku?->judul ?? '?';
                $qty   = max(1, (int) $item->qty);

                return $qty > 1 ? $judul.' ('.$qty.'×)' : $judul;
            })->implode(', ');

            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $bookList ?: '-',
                $row->loan_date,
                $row->due_date,
                ucfirst($row->latestStatus()),
            ];
        });
    }
}
