<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Guru;
use App\Models\JadwalAbsen;
use App\Models\Kelas;
use App\Models\Pelajaran;
use App\Models\Sekolah;
use App\Services\JadwalAbsenSyncService;
use App\Support\AdminSchoolScope;
use App\Support\SoftDeleteRules;
use App\Support\Weekday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JadwalAbsenController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private JadwalAbsenSyncService $syncService
    ) {}

    public function index(): View
    {
        return view('admin.absensi.jadwal-absen.index', [
            'title' => 'Jadwal Absen',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function create(): View
    {
        return view('admin.absensi.jadwal-absen.form', array_merge(
            $this->formOptions(),
            [
                'title' => 'Tambah Jadwal Absen',
                'jadwal' => null,
                'formAction' => route('admin.absensi.jadwal-absen.store'),
                'formMethod' => 'POST',
            ]
        ));
    }

    public function edit(JadwalAbsen $jadwalAbsen): View
    {
        $jadwalAbsen->load(['sekolah', 'kelas', 'hari.slots.pelajaran', 'hari.slots.guru']);

        return view('admin.absensi.jadwal-absen.form', array_merge(
            $this->formOptions(),
            [
                'title' => 'Edit Jadwal Absen',
                'jadwal' => $jadwalAbsen,
                'formAction' => route('admin.absensi.jadwal-absen.update', $jadwalAbsen),
                'formMethod' => 'PUT',
            ]
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $query = JadwalAbsen::query()
            ->with(['sekolah', 'kelas', 'hari.slots'])
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->sekolah_id))
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name'],
            'orderable' => ['name', 'created_at'],
        ], function (JadwalAbsen $row) {
            $activeDays = $row->hari
                ->where('is_active', true)
                ->pluck('day_of_week')
                ->sort()
                ->map(fn ($d) => Weekday::label((int) $d))
                ->implode(', ');

            $slotCount = $row->hari->sum(fn ($hari) => $hari->slots->count());

            $assignment = $row->assignmentLabel();

            return [
                $row->name,
                $row->sekolah?->name ?? 'Semua sekolah',
                $assignment,
                $activeDays ?: '-',
                (string) $slotCount,
                $this->badgeCell($row->is_active ? 'Aktif' : 'Nonaktif', $row->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell(view('admin.absensi.jadwal-absen.partials.row-actions', ['jadwal' => $row])->render(), null, 'action'),
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('attendance.create');

        $payload = $this->validatedPayload($request);
        $jadwal = $this->syncService->sync(new JadwalAbsen, $payload);

        return $this->jsonSuccess('Jadwal absen berhasil ditambahkan.', ['id' => $jadwal->id], 201);
    }

    public function update(Request $request, JadwalAbsen $jadwalAbsen): JsonResponse
    {
        $this->authorize('attendance.update');

        $payload = $this->validatedPayload($request);
        $jadwal = $this->syncService->sync($jadwalAbsen, $payload);

        return $this->jsonSuccess('Jadwal absen berhasil diperbarui.', ['id' => $jadwal->id]);
    }

    public function destroy(JadwalAbsen $jadwalAbsen): JsonResponse
    {
        $this->authorize('attendance.delete');

        $jadwalAbsen->hari()->each(function ($hari) {
            $hari->slots()->delete();
            $hari->delete();
        });
        $jadwalAbsen->kelas()->detach();
        $jadwalAbsen->delete();

        return $this->jsonSuccess('Jadwal absen berhasil dihapus.');
    }

    public function previewStudents(Request $request): JsonResponse
    {
        $payload = $this->resolveScopePayload($request);
        $request->validate([
            'assignment_type' => ['required', Rule::in(['kelas', 'sekolah'])],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => [SoftDeleteRules::exists('kelas')],
        ]);

        $jadwal = new JadwalAbsen([
            'sekolah_id' => $payload['sekolah_id'],
            'assignment_type' => $request->assignment_type,
        ]);

        if ($request->assignment_type === 'kelas') {
            $jadwal->setRelation('kelas', Kelas::whereIn('id', $request->kelas_ids ?? [])->get());
        }

        return $this->jsonSuccess('OK', [
            'count' => $jadwal->resolvedStudentCount(),
        ]);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'schools' => AdminSchoolScope::schools(),
            'classes' => AdminSchoolScope::kelasList()->load('sekolah:id,name'),
            'pelajaranList' => Pelajaran::with('sekolah:id,name')->where('is_active', true)->orderBy('name')->get(),
            'guruList' => Guru::where('status', 'aktif')->orderBy('name')->get(),
            'weekdays' => Weekday::labels(),
        ];
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request): array
    {
        $scope = $this->resolveScopePayload($request);

        $validated = $request->validate([
            'sekolah_id' => ['nullable'],
            'name' => ['required', 'string', 'max:120'],
            'assignment_type' => ['required', Rule::in(['kelas', 'sekolah'])],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => [SoftDeleteRules::exists('kelas')],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'days' => ['required', 'array'],
            'days.*.active' => ['nullable'],
            'days.*.slots' => ['nullable', 'array'],
            'days.*.slots.*.pelajaran_id' => ['nullable', SoftDeleteRules::exists('pelajaran')],
            'days.*.slots.*.guru_id' => ['nullable', SoftDeleteRules::exists('guru')],
            'days.*.slots.*.time_start' => ['nullable', 'date_format:H:i'],
            'days.*.slots.*.time_end' => ['nullable', 'date_format:H:i'],
            'days.*.slots.*.tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);

        $validated['sekolah_id'] = $scope['sekolah_id'];

        if ($validated['assignment_type'] === 'sekolah') {
            $validated['kelas_ids'] = [];
        } elseif (empty($validated['kelas_ids'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'kelas_ids' => ['Pilih minimal satu kelas untuk penugasan per kelas.'],
            ]);
        }

        if (! empty($validated['kelas_ids'])) {
            $kelasQuery = Kelas::query()->whereIn('id', $validated['kelas_ids']);
            if ($validated['sekolah_id'] !== null) {
                $kelasQuery->where('sekolah_id', $validated['sekolah_id']);
            }
            if ($kelasQuery->count() !== count(array_unique($validated['kelas_ids']))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'kelas_ids' => ['Kelas yang dipilih tidak valid untuk sekolah yang dipilih.'],
                ]);
            }
        }

        $hasActiveDay = false;
        foreach (Weekday::numbers() as $day) {
            $dayData = $validated['days'][$day] ?? $validated['days'][(string) $day] ?? null;
            if (! filter_var($dayData['active'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $hasActiveDay = true;
            $slots = $dayData['slots'] ?? [];
            if ($slots === []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "days.{$day}.slots" => ['Tambahkan minimal satu pelajaran untuk '.Weekday::label($day).'.'],
                ]);
            }

            foreach ($slots as $index => $slot) {
                foreach (['pelajaran_id', 'guru_id', 'time_start', 'time_end'] as $field) {
                    if (empty($slot[$field])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "days.{$day}.slots.{$index}.{$field}" => ['Field wajib diisi untuk slot pelajaran.'],
                        ]);
                    }
                }

                if ($slot['time_end'] <= $slot['time_start']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "days.{$day}.slots.{$index}.time_end" => ['Waktu selesai harus setelah waktu mulai.'],
                    ]);
                }
            }
        }

        if (! $hasActiveDay) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'days' => ['Centang minimal satu hari aktif dalam minggu.'],
            ]);
        }

        return $validated;
    }

    /** @return array{sekolah_id: ?int} */
    private function resolveScopePayload(Request $request): array
    {
        $scoped = AdminSchoolScope::operatorSekolahId($request->user());
        if ($scoped !== null) {
            return ['sekolah_id' => $scoped];
        }

        $raw = $request->input('sekolah_id');
        if ($raw === null || $raw === '' || $raw === 'all') {
            return ['sekolah_id' => null];
        }

        $request->validate([
            'sekolah_id' => [SoftDeleteRules::exists('sekolah')],
        ]);

        return ['sekolah_id' => (int) $raw];
    }
}
