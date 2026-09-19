<?php

namespace App\Http\Controllers\Portal\Perizinan;

use App\Http\Controllers\Admin\Perizinan\DashboardController as BaseController;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends BaseController
{
    public function index(Request $request): View
    {
        $view = parent::index($request);
        $data = $view->getData();
        $data['title'] = '';
        $data['isPortal'] = true;
        $data['rekapUrl'] = route('portal.perizinan.rekap-laporan');

        return view('admin.perizinan.dashboard', $data);
    }
}
