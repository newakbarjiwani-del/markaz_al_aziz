<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UiComponentsController extends Controller
{
    public function index(): View
    {
        return view('admin.komponen-ui.index', [
            'title' => 'UI Components',
        ]);
    }
}
