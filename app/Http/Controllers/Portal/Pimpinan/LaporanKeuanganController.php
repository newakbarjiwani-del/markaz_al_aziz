<?php

namespace App\Http\Controllers\Portal\Pimpinan;

use App\Http\Controllers\Admin\Finance\LaporanKeuanganController as BaseController;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanKeuanganController extends BaseController
{
    public function index(Request $request): View
    {
        $view = parent::index($request);
        $data = $view->getData();

        return view('admin.keuangan.laporan-keuangan', array_merge($data, [
            'ajaxUrl' => route('portal.pimpinan.laporan-keuangan.data'),
        ]));
    }
}
