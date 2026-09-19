<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function show(string $title, ?string $description = null): View
    {
        return view('admin.placeholder', [
            'title' => $title,
            'description' => $description ?? 'Halaman ini akan segera tersedia — sedang dalam pengembangan.',
        ]);
    }
}
