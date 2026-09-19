<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function index(): View
    {
        $this->authorize('tahfidz.view');

        return view('admin.tahfidz.placeholder', [
            'title' => 'Jadwal Murajaah',
            'description' => 'Jadwal murajaah in-app akan tersedia setelah Phase 3.',
        ]);
    }
}
