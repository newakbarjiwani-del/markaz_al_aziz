<?php

namespace App\Http\Controllers\Admin\Booklet;

use App\Http\Controllers\Controller;
use App\Models\Booklet;
use App\Support\AdminSchoolScope;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('booklet.view');

        $query = Booklet::query();
        AdminSchoolScope::applyWithGlobal($query);

        return view('admin.booklet.dashboard', [
            'title' => 'Dashboard Booklet',
            'stats' => [
                ['label' => 'Total Booklet', 'value' => (clone $query)->count(), 'accent' => 'primary'],
                ['label' => 'Terbit', 'value' => (clone $query)->where('is_published', true)->count(), 'accent' => 'green'],
                ['label' => 'Draf', 'value' => (clone $query)->where('is_published', false)->count(), 'accent' => 'neutral'],
            ],
        ]);
    }
}
