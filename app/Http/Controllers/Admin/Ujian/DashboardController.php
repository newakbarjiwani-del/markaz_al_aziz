<?php

namespace App\Http\Controllers\Admin\Ujian;

use App\Http\Controllers\Controller;
use App\Models\Ujian;
use App\Models\UjianAttempt;
use App\Support\AdminSchoolScope;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('ujian.view');

        $ujianQuery = Ujian::query();
        AdminSchoolScope::applyWithGlobal($ujianQuery);

        $attemptQuery = UjianAttempt::query()->whereHas('ujian', function ($q): void {
            AdminSchoolScope::applyWithGlobal($q);
        });

        return view('admin.ujian.dashboard', [
            'title' => 'Dashboard Ujian Online',
            'stats' => [
                ['label' => 'Total Ujian', 'value' => (clone $ujianQuery)->count(), 'accent' => 'primary'],
                ['label' => 'Dipublikasikan', 'value' => (clone $ujianQuery)->where('status', Ujian::STATUS_PUBLISHED)->count(), 'accent' => 'green'],
                ['label' => 'Draft', 'value' => (clone $ujianQuery)->where('status', Ujian::STATUS_DRAFT)->count(), 'accent' => 'neutral'],
                ['label' => 'Percobaan Terkumpul', 'value' => (clone $attemptQuery)->where('status', UjianAttempt::STATUS_SUBMITTED)->count(), 'accent' => 'info'],
            ],
        ]);
    }
}
