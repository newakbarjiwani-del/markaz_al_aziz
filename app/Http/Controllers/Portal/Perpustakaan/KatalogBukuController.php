<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\KatalogBukuController as BaseController;
use Illuminate\View\View;

class KatalogBukuController extends BaseController
{
    public function index(): View
    {
        $this->authorize('library.view');

        return view('admin.perpustakaan.katalog-buku', $this->katalogViewData(
            ajaxUrl: route('portal.perpustakaan.katalog-buku.data'),
            storeUrl: route('portal.perpustakaan.katalog-buku.store'),
        ));
    }

    protected function katalogUpdateRouteName(): string
    {
        return 'portal.perpustakaan.katalog-buku.update';
    }

    protected function katalogDestroyRouteName(): string
    {
        return 'portal.perpustakaan.katalog-buku.destroy';
    }
}
