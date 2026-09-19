<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\DashboardController as BaseController;
use Illuminate\View\View;

class DashboardController extends BaseController
{
    public function index(): View
    {
        $data = parent::index()->getData();
        $data['title'] = '';
        $data['isPortal'] = true;

        return view('admin.perpustakaan.dashboard', $data);
    }
}
