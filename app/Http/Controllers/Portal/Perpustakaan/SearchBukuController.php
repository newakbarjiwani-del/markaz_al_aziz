<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\SearchBukuController as BaseController;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchBukuController extends BaseController
{
    public function index(Request $request): View
    {
        $view = parent::index($request);

        return view('admin.perpustakaan.cari-buku', array_merge($view->getData(), [
            'searchAction' => route('portal.perpustakaan.cari-buku'),
        ]));
    }
}
