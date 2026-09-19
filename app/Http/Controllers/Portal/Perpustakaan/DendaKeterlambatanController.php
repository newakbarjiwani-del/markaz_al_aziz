<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\DendaKeterlambatanController as BaseController;
use Illuminate\View\View;

class DendaKeterlambatanController extends BaseController
{
    public function index(): View
    {
        $view = parent::index();

        return view('admin.perpustakaan.denda-keterlambatan', array_merge($view->getData(), [
            'ajaxUrl' => route('portal.perpustakaan.denda-keterlambatan.data'),
        ]));
    }
}
