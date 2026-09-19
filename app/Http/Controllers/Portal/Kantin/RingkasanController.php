<?php

namespace App\Http\Controllers\Portal\Kantin;

use App\Http\Controllers\Controller;
use App\Http\Traits\KantinPortal;
use Illuminate\View\View;

class RingkasanController extends Controller
{
    use KantinPortal;

    public function index(): View
    {
        return view('portal.kantin.ringkasan', [
            'title' => 'Ringkasan Hari Ini',
            'stats' => $this->kantinDashboardStats(),
        ]);
    }
}
