<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryPeminjamanController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.perpustakaan.riwayat-peminjaman', ['title' => 'History Peminjaman']);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Peminjaman::query()
            ->select('peminjaman.*')
            ->with(['items.buku', 'siswa', 'guru']);

        return $this->datatableResponse($request, $query, [
            'searchable' => [
                'borrower_type',
                'tamu_nama',
                'siswa.name',
                'siswa.nis',
                'guru.name',
                'guru.nip',
            ],
            'orderable' => ['loan_date', 'created_at', 'borrower_type'],
        ], function (Peminjaman $row) {
            $bookList = $row->items->map(function ($item) {
                $judul = $item->buku?->judul ?? '?';
                $qty   = max(1, (int) $item->qty);

                return $qty > 1 ? $judul.' ('.$qty.'×)' : $judul;
            })->implode(', ');

            $status = $row->items->every(fn ($i) => $i->status === 'dikembalikan')
                ? 'Dikembalikan'
                : 'Dipinjam';

            return [
                $row->borrowerTypeLabel(),
                $row->borrowerIdentifier(),
                $row->borrowerName(),
                $bookList ?: '-',
                $row->loan_date,
                $row->due_date,
                $status,
            ];
        });
    }
}
