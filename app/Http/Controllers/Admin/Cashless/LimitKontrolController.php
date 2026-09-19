<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\LimitCashless;
use App\Models\Siswa;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\CashlessSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LimitKontrolController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        $sekolahId = AdminSchoolScope::resolveForStore(request());

        return view('admin.dompet-digital.limit-kontrol', [
            'title' => 'Limit & Kontrol',
            'globalDailyLimit' => CashlessSettings::dailyTransactionLimit($sekolahId),
            'cashlessSettingsUrl' => route('admin.pengaturan-modul.show', 'cashless'),
            'classes' => $this->classesList(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Siswa::query()
            ->with('kelas')
            ->when($request->filled('has_custom_limit'), function ($q) use ($request) {
                if ($request->boolean('has_custom_limit')) {
                    $q->whereNotNull('daily_transaction_limit');
                } else {
                    $q->whereNull('daily_transaction_limit');
                }
            });

        $this->applyKelasFilter($query, $request, '_self');

        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());
            if ($term !== '') {
                $like = '%'.$term.'%';
                $query->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('nis', 'like', $like);
                });
            }
        }

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nis', 'name'],
            'orderable' => ['nis', 'name', 'kelas_id', 'daily_transaction_limit'],
        ], function (Siswa $siswa) {
            $hasCustom = $siswa->daily_transaction_limit !== null;
            $limitCell = $hasCustom
                ? $this->cell(
                    'Rp '.number_format((float) $siswa->daily_transaction_limit, 0, ',', '.'),
                    (int) $siswa->daily_transaction_limit,
                    'number'
                )
                : $this->cell('<span class="text-muted">Global</span>', null, 'text');

            return [
                $siswa->nis,
                $siswa->name,
                $siswa->kelas?->name ?? '-',
                $limitCell,
                $this->cell(
                    view('admin.dompet-digital.partials.student-limit-actions', ['siswa' => $siswa])->render(),
                    null,
                    'action'
                ),
            ];
        });
    }

    public function updateGlobal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'daily_transaction_limit' => ['required', 'numeric', 'min:0'],
        ]);

        $settings = CashlessSettings::updateForSekolah(
            AdminSchoolScope::resolveForStore($request),
            $data
        );

        return $this->jsonSuccess(
            'Limit global harian berhasil diperbarui: '.ActionMessage::rupiah((int) $settings->daily_transaction_limit).'.',
            [
                'daily_transaction_limit' => (float) $settings->daily_transaction_limit,
            ]
        );
    }

    public function updateSiswa(Request $request, Siswa $siswa): JsonResponse
    {
        $data = $request->validate([
            'daily_transaction_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $limit = $data['daily_transaction_limit'] ?? null;
        if ($limit === '' || $limit === null) {
            $limit = null;
        } else {
            $limit = (float) $limit;
        }

        $siswa->update(['daily_transaction_limit' => $limit]);
        $siswa->load('kelas');

        $message = $limit === null
            ? 'Limit siswa dikembalikan ke limit global'
            : 'Limit harian siswa berhasil diperbarui: '.ActionMessage::rupiah((int) $limit);

        return $this->jsonSuccess(
            ActionMessage::withSubject($message, ActionMessage::siswa($siswa)),
            $siswa->fresh(['kelas'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:siswa,kelas,sekolah',
            'target' => 'nullable|string|max:100',
            'category' => 'required|string|max:100',
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
        ]);

        $category = trim((string) $data['category']);
        if ($category === '') {
            return $this->jsonError('Kategori wajib diisi untuk limit kategori.', [
                'category' => ['Kategori wajib diisi.'],
            ]);
        }

        $target = trim((string) ($data['target'] ?? ''));
        if ($data['type'] === 'sekolah') {
            $target = $target !== '' ? $target : 'Semua';
        } elseif ($target === '') {
            return $this->jsonError('Target wajib diisi untuk limit kelas/siswa.', [
                'target' => ['Target wajib diisi.'],
            ]);
        }

        $limit = LimitCashless::create([
            'sekolah_id' => AdminSchoolScope::resolveForStore($request),
            'type' => $data['type'],
            'target' => $target,
            'category' => $category,
            'daily_limit' => $data['daily_limit'] ?? null,
            'monthly_limit' => $data['monthly_limit'] ?? null,
        ]);

        $subject = implode(' · ', array_filter([
            ucfirst($limit->type),
            $limit->target,
            $limit->category,
        ], fn (?string $value) => filled($value)));

        return $this->jsonSuccess(
            ActionMessage::withSubject('Limit kategori berhasil ditambahkan', $subject),
            $limit,
            201
        );
    }
}
