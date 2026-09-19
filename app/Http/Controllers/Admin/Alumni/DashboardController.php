<?php

namespace App\Http\Controllers\Admin\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\AlumniTracer;
use App\Support\AdminSchoolScope;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('alumni.view');

        $alumniQuery = Alumni::query();
        AdminSchoolScope::applyWithGlobal($alumniQuery);

        $tracerQuery = AlumniTracer::query()->whereHas('alumni', function ($q): void {
            AdminSchoolScope::applyWithGlobal($q);
        });

        return view('admin.alumni.dashboard', [
            'title' => 'Dashboard Alumni',
            'stats' => [
                ['label' => 'Total Alumni', 'value' => (clone $alumniQuery)->count(), 'accent' => 'primary'],
                ['label' => 'Aktif', 'value' => (clone $alumniQuery)->where('is_active', true)->count(), 'accent' => 'green'],
                ['label' => 'Respons Tracer', 'value' => (clone $tracerQuery)->count(), 'accent' => 'info'],
                ['label' => 'Tahun Ini', 'value' => (clone $tracerQuery)->where('tahun_tracer', (string) now()->year)->count(), 'accent' => 'warning'],
            ],
        ]);
    }
}
