<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\StorePublicAlumniTracerRequest;
use App\Models\AlumniTracer;
use App\Models\Sekolah;
use App\Services\AlumniTracerService;
use App\Support\AlumniTracerStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TracerController extends Controller
{
    public function create(): View
    {
        return view('alumni.tracer', [
            'title' => 'Tracer Study Alumni',
            'schools' => Sekolah::query()->where('is_active', true)->orderBy('name')->get(),
            'statusLabels' => AlumniTracerStatus::labels(),
            'defaultYear' => (string) now()->year,
        ]);
    }

    public function store(StorePublicAlumniTracerRequest $request, AlumniTracerService $service): RedirectResponse
    {
        $data = $request->validated();
        $data['source'] = AlumniTracer::SOURCE_PUBLIC;

        $service->submit($data);

        return redirect()
            ->route('alumni.tracer')
            ->with('success', 'Terima kasih. Respons tracer study Anda telah dicatat.');
    }
}
