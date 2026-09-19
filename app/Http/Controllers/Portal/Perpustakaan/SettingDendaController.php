<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\SettingDendaController as BaseController;
use Illuminate\View\View;

class SettingDendaController extends BaseController
{
    public function index(): View
    {
        return view('portal.perpustakaan.setting-denda', $this->viewData());
    }

    protected function viewData(): array
    {
        return array_merge(parent::viewData(), [
            'formAction' => route('portal.perpustakaan.setting-denda.update'),
        ]);
    }
}
