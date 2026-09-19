<?php

namespace App\Http\Controllers\Portal\Kantin;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\KantinPortal;
use App\Models\SccttranCashless;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use KantinPortal;

    public function index(): View
    {
        $classes = $this->kantinSekolahId()
            ? \App\Models\Kelas::query()
                ->where('sekolah_id', $this->kantinSekolahId())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sekolah_id'])
            : $this->classesList();

        return view('portal.kantin.transaksi', [
            'title' => 'Transaksi Kantin',
            'classes' => $classes,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->kantinBelanjaQuery(onlyCurrentOperator: true)
            ->when($request->filled('wallet'), fn ($q) => $q->where('wallet', $request->string('wallet')));

        $this->applyDateRange($query, $request, 'TRXDATE');
        $this->applyKelasFilter($query, $request, 'siswa');
        $this->applySiswaSearchFilter($query, $request, 'siswa');

        return $this->datatableResponse($request, $query->orderByDesc('TRXDATE')->orderByDesc('id'), [
            'searchable' => ['description', 'wallet', 'siswa.name', 'siswa.nis', 'user.name', 'user.username'],
            'orderable' => ['TRXDATE', 'TRXDATE', 'TRXDATE', 'TRXDATE', 'wallet', 'DEBET', 'description', 'TRXDATE'],
        ], function (SccttranCashless $row) {
            $petugas = $row->user
                ? trim(($row->user->name ?: $row->user->username) ?: '-')
                : '-';

            return [
                $row->TRXDATE,
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $this->cashlessWalletLabel($row->wallet),
                $this->cell('Rp '.number_format((int) $row->DEBET, 0, ',', '.'), (int) $row->DEBET, 'number'),
                $row->description ?: '-',
                $petugas,
            ];
        });
    }
}
