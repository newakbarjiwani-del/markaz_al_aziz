<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Traits\KantinPortal;
use Illuminate\View\View;

class KantinDashboardController extends Controller
{
    use KantinPortal;

    public function index(): View
    {
        $recentTransactions = $this->kantinBelanjaQuery(onlyCurrentOperator: true)
            ->latest('TRXDATE')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('portal.kantin.dashboard', [
            'title' => '',
            'stats' => $this->kantinDashboardStats(),
            'recentTransactions' => $recentTransactions,
            'operator' => auth()->user()->loadMissing('sekolah'),
        ]);
    }
}
