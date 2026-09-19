<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\PeminjamanBuku;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DendaKeterlambatanController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $totalFine = PeminjamanBuku::where('fine_amount', '>', 0)->sum('fine_amount');

        return view('admin.perpustakaan.denda-keterlambatan', [
            'title' => 'Denda Keterlambatan',
            'totalFine' => $totalFine,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PeminjamanBuku::query()
            ->select('peminjaman_buku.*')
            ->join('peminjaman', 'peminjaman_buku.peminjaman_id', '=', 'peminjaman.id')
            ->with(['peminjaman.siswa', 'peminjaman.guru', 'buku'])
            ->where('fine_amount', '>', 0);

        return $this->datatableResponse($request, $query, [
            'searchable' => [
                'peminjaman_buku.status',
                'peminjaman.tamu_nama',
                'peminjaman.siswa.name',
                'peminjaman.siswa.nis',
                'peminjaman.guru.name',
                'peminjaman.guru.nip',
                'buku.judul',
            ],
            'orderable' => ['fine_amount', 'return_date', 'peminjaman.borrower_type', 'peminjaman_buku.created_at'],
        ], function (PeminjamanBuku $row) {
            $daysLate = ($row->return_date && $row->due_date && $row->return_date->gt($row->due_date))
                ? $row->due_date->diffInDays($row->return_date)
                : 0;

            $qty = max(1, (int) $row->qty);

            return [
                $row->borrowerTypeLabel(),
                $row->borrowerIdentifier(),
                $row->borrowerName(),
                $qty > 1 ? $qty.'x' : '',
                $row->buku?->judul ?? '-',
                $row->due_date,
                $row->return_date,
                $daysLate.' hari',
                'Rp '.number_format($row->fine_amount, 0, ',', '.'),
            ];
        });
    }
}
