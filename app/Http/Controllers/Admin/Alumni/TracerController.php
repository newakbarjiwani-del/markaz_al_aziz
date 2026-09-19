<?php

namespace App\Http\Controllers\Admin\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\AlumniTracer;
use App\Support\AdminSchoolScope;
use App\Support\AlumniTracerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TracerController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('alumni.view');

        return view('admin.alumni.tracer', [
            'title' => 'Tracer Study',
            'statusLabels' => AlumniTracerStatus::labels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('alumni.view');

        $query = AlumniTracer::query()
            ->with(['alumni.sekolah'])
            ->when($request->filled('tahun_tracer'), fn ($q) => $q->where('tahun_tracer', $request->string('tahun_tracer')))
            ->when($request->filled('status_lulusan'), fn ($q) => $q->where('status_lulusan', $request->string('status_lulusan')))
            ->whereHas('alumni', function ($q) use ($request): void {
                AdminSchoolScope::applyWithGlobal($q);
                if ($request->filled('sekolah_id')) {
                    $q->where('sekolah_id', $request->integer('sekolah_id'));
                }
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['tahun_tracer', 'institusi', 'jabatan', 'kota'],
            'orderable' => ['tahun_tracer', 'status_lulusan', 'submitted_at'],
        ], function (AlumniTracer $tracer) {
            $alumni = $tracer->alumni;

            return [
                $alumni?->name ?? '-',
                $alumni?->nis ?: '-',
                $tracer->tahun_tracer,
                AlumniTracerStatus::label($tracer->status_lulusan),
                $tracer->institusi ?: '-',
                $this->dateCell($tracer->submitted_at),
                $this->badgeCell(
                    $tracer->source === AlumniTracer::SOURCE_PUBLIC ? 'Publik' : 'Admin',
                    $tracer->source === AlumniTracer::SOURCE_PUBLIC ? 'badge badge-blue' : 'badge badge-slate'
                ),
            ];
        });
    }
}
