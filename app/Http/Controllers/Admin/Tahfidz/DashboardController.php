<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\TahfidzProgress;
use App\Models\TahfidzTarget;
use App\Support\AdminSchoolScope;
use App\Support\TahfidzProgressStatus;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('tahfidz.view');

        $targets = TahfidzTarget::query();
        AdminSchoolScope::apply($targets);

        $progress = TahfidzProgress::query();
        AdminSchoolScope::apply($progress);

        return view('admin.tahfidz.dashboard', [
            'title' => 'Dashboard Tahfidz',
            'stats' => [
                [
                    'label' => 'Target Aktif',
                    'value' => (clone $targets)->where(function ($q) {
                        $q->whereNull('due_date')->orWhereDate('due_date', '>=', now()->toDateString());
                    })->count(),
                    'accent' => 'primary',
                ],
                [
                    'label' => 'Progress Lancar/Mutqin',
                    'value' => (clone $progress)->whereIn('status', [
                        TahfidzProgressStatus::LANCAR,
                        TahfidzProgressStatus::MUTQIN,
                    ])->count(),
                    'accent' => 'green',
                ],
                [
                    'label' => 'Masih Proses',
                    'value' => (clone $progress)->where('status', TahfidzProgressStatus::PROSES)->count(),
                    'accent' => 'warning',
                ],
            ],
        ]);
    }
}
