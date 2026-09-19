<?php

namespace App\Http\Controllers\Portal\Kantin;

use App\Http\Controllers\Controller;
use App\Http\Traits\KantinPortal;
use Illuminate\View\View;

class MenuController extends Controller
{
    use KantinPortal;

    public function index(): View
    {
        $menus = $this->kantinMenuQuery()->orderBy('category')->orderBy('name')->get();

        return view('portal.kantin.menu', [
            'title' => 'Menu Kantin',
            'menus' => $menus,
        ]);
    }
}
