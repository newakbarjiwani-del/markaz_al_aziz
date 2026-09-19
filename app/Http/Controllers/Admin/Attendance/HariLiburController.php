<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreHariLiburRequest;
use App\Http\Requests\Attendance\UpdateHariLiburRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\HariLibur;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Support\DisplayDate;

class HariLiburController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        return view('admin.absensi.hari-libur.index', [
            'title' => 'Hari Libur',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = HariLibur::query()
            ->with('sekolah')
            ->when($request->filled('sekolah_id'), function ($q) use ($request) {
                if ($request->input('sekolah_id') === 'global') {
                    $q->whereNull('sekolah_id');
                } else {
                    $q->where('sekolah_id', $request->integer('sekolah_id'));
                }
            })
            ->when($request->filled('applies_to'), fn ($q) => $q->where('applies_to', $request->applies_to));

        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'notes'],
            'orderable' => ['date', 'name', 'created_at'],
        ], function (HariLibur $row) {
            $fields = $row->only(['sekolah_id', 'date', 'name', 'applies_to', 'notes']);

            return [
                $row->date,
                $row->sekolah?->name ?? 'Semua sekolah',
                $row->name,
                $row->appliesToLabel(),
                $row->notes ? \Illuminate\Support\Str::limit($row->notes, 60) : '-',
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.absensi.hari-libur.update', $row),
                            'form_target' => 'hari-libur-form',
                            'modal_target' => 'hari-libur-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.absensi.hari-libur.destroy', $row),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus hari libur ini?',
                            'confirm_detail' => [
                                ['label' => 'Nama', 'value' => $row->name],
                                ['label' => 'Tanggal', 'value' => DisplayDate::date($row->date)],
                                ['label' => 'Sekolah', 'value' => $row->sekolah?->name ?? 'Semua sekolah'],
                                ['label' => 'Berlaku untuk', 'value' => $row->appliesToLabel()],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreHariLiburRequest $request): JsonResponse
    {
        $data = $request->validated();
        $sekolahId = $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null;
        $defaultNotes = $data['notes'] ?? null;
        $appliesTo = $data['applies_to'];
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);

        $created = DB::transaction(function () use ($data, $sekolahId, $defaultNotes, $appliesTo) {
            $rows = [];

            foreach ($data['entries'] as $entry) {
                $notes = filled($entry['notes'] ?? null) ? $entry['notes'] : $defaultNotes;

                $rows[] = HariLibur::create([
                    'sekolah_id' => $sekolahId,
                    'date' => $entry['date'],
                    'name' => $entry['name'],
                    'applies_to' => $appliesTo,
                    'notes' => $notes,
                ]);
            }

            return $rows;
        });

        $count = count($created);
        $message = $count === 1
            ? 'Hari libur berhasil ditambahkan.'
            : "{$count} hari libur berhasil ditambahkan.";

        return $this->jsonSuccess($message, ['count' => $count, 'items' => $created], 201);
    }

    public function update(UpdateHariLiburRequest $request, HariLibur $hariLibur): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);

        $hariLibur->update($data);

        return $this->jsonSuccess('Hari libur berhasil diperbarui.', $hariLibur);
    }

    public function destroy(HariLibur $hariLibur): JsonResponse
    {
        $this->authorize('attendance.delete');

        $hariLibur->delete();

        return $this->jsonSuccess('Hari libur berhasil dihapus.');
    }
}
