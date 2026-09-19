<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Support\DashboardChart;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $byStatus = PeminjamanBuku::query()
            ->select('status', DB::raw('COALESCE(SUM(qty), 0) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalStok = (int) Buku::sum('jumlah');
        $tersedia = (int) Buku::sum('tersedia');
        $dipinjam = max(0, $totalStok - $tersedia);

        $charts = [
            DashboardChart::single(
                'library-stok-chart',
                'Stok Buku',
                'doughnut',
                ['Tersedia', 'Dipinjam'],
                [$tersedia, $dipinjam]
            ),
        ];

        if ($byStatus->isNotEmpty()) {
            $charts[] = DashboardChart::doughnutFromMap(
                'library-status-chart',
                'Status Peminjaman',
                $byStatus->all()
            );
        }

        return view('admin.perpustakaan.dashboard', [
            'title' => 'Dashboard Perpustakaan',
            'stats' => [
                ['label' => 'Total Buku', 'value' => Buku::count(), 'accent' => 'primary'],
                ['label' => 'Tersedia', 'value' => $tersedia, 'accent' => 'green'],
                ['label' => 'Sedang Dipinjam', 'value' => (int) PeminjamanBuku::where('peminjaman_buku.status', 'dipinjam')->sum('qty'), 'accent' => 'accent'],
                ['label' => 'Terlambat', 'value' => (int) PeminjamanBuku::where('peminjaman_buku.status', 'dipinjam')->whereHas('peminjaman', fn ($q) => $q->whereDate('due_date', '<', today()))->sum('qty'), 'accent' => 'red'],
            ],
            'charts' => $charts,
            'recentPeminjaman' => Peminjaman::with(['items.buku', 'siswa', 'guru'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
