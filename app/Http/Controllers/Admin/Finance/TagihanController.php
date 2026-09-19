<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdateTagihanRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\JenisTagihan;
use App\Models\LogTagihanEdit;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\PaymentMethod;
use App\Support\SoftDeleteRules;
use App\Services\Finance\PotonganTagihanService;
use App\Services\Finance\TagihanCicilanService;
use App\Support\TagihanPeriode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TagihanController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        $jenisTagihanList = $this->sortJenisTagihanForSelect(
            $this->uniqueCatalogByName(JenisTagihan::query()->active()->get())
        );
        $sppJenis = $this->sppJenisTagihan();
        $tahunAkademikList = $this->uniqueCatalogByName(
            TahunAkademik::query()
                ->orderByDesc('is_active')
                ->orderByDesc('name')
                ->get(['id', 'name', 'is_active'])
        );

        return view('admin.keuangan.tagihan', [
            'title' => 'Tagihan',
            'schools' => AdminSchoolScope::schools(),
            'classes' => AdminSchoolScope::kelasList()->load('sekolah:id,name'),
            'jenisTagihanList' => $jenisTagihanList,
            'operatorSchoolId' => AdminSchoolScope::operatorSekolahId(),
            'jenisTagihanFlat' => $jenisTagihanList->map(fn (JenisTagihan $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'default_amount' => (int) ($item->default_amount ?? 0),
                'is_spp' => $item->is_spp,
            ])->values(),
            'tahunAkademikFlat' => $tahunAkademikList->map(fn (TahunAkademik $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'is_active' => $item->is_active,
                'start_year' => (int) substr($item->name, 0, 4),
                'end_year' => (int) substr($item->name, 5, 4),
            ])->values(),
            'tahunAkademikList' => $tahunAkademikList,
            'defaultJenisTagihanId' => $sppJenis?->id,
            'defaultSppAmount' => $this->defaultSppAmount(),
            'defaultDueDate' => now()->addDays($this->defaultDueDay())->toDateString(),
            'defaultPeriode' => TagihanPeriode::toMonthInput(TagihanPeriode::current()),
            'stats' => [
                'total' => Tagihan::rootBill()->count(),
                'lunas' => Tagihan::rootBill()->paid()->count(),
                'belum_lunas' => Tagihan::rootBill()->unpaid()->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Tagihan::query()
            ->rootBill()
            ->with(['siswa.kelas', 'tahunAkademik'])
            ->withCount(['cicilanChildren', 'potonganPemakaian'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', (int) $request->status))
            ->when(
                in_array((string) $request->input('is_cicilan'), ['0', '1'], true),
                fn ($q) => $q->where('is_cicilan', (int) $request->input('is_cicilan'))
            )
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->jenis))
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('periode'), function ($q) use ($request) {
                $periode = TagihanPeriode::normalize($request->periode);
                if ($periode !== null) {
                    $q->where('periode', $periode);
                }
            });

        $this->applyKelasFilter($query, $request);
        $this->applyDateRange($query, $request, 'due_date');
        $this->applyDateRange($query, $request, 'paid_dt', 'paid_dt_from', 'paid_dt_to');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['jenis', 'periode', 'fidbank', 'siswa.name', 'siswa.nis'],
            // Keep indexes aligned with 19 displayed columns.
            'orderable' => [
                'created_at', 'created_at', 'created_at', 'created_at',
                'created_at', 'jenis', 'periode', 'urutan',
                'amount_bruto', 'potongan_amount', 'amount', 'paid', 'amount',
                'status', 'is_cicilan', 'fidbank', 'paid_dt', 'due_date', 'created_at',
            ],
        ], function (Tagihan $tagihan) {
            $bruto = (int) round($tagihan->displayBruto());
            $potongan = (int) round((float) $tagihan->potongan_amount);
            $nominal = (int) round((float) $tagihan->amount);
            $sisa = (int) round($tagihan->remaining());
            $methodLabel = PaymentMethod::label($tagihan->fidbank);
            $canModify = ! $tagihan->isBillingLocked();
            $detail = $canModify
                ? [
                    ['label' => 'Siswa', 'value' => $tagihan->siswa?->name.' ('.($tagihan->siswa?->nis ?? '-').')'],
                    ['label' => 'Jenis', 'value' => $tagihan->jenis],
                    ['label' => 'Periode', 'value' => $tagihan->displayPeriode()],
                    ['label' => 'Nominal', 'value' => 'Rp '.number_format($nominal, 0, ',', '.')],
                ]
                : [];

            return [
                $this->cell($tagihan->siswa?->nis ?? '-', $tagihan->siswa?->nis ?? '-', 'text'),
                $this->cell($tagihan->siswa?->virtualAccountNumber() ?? '-', $tagihan->siswa?->virtualAccountNumber() ?? '-', 'text'),
                $tagihan->siswa?->name ?? '-',
                $tagihan->siswa?->kelas?->name ?? '-',
                $tagihan->tahunAkademik?->name ?? '-',
                $tagihan->jenis,
                $tagihan->displayPeriode(),
                $tagihan->urutan ?? '-',
                $this->cell('Rp '.number_format($bruto, 0, ',', '.'), $bruto, 'number'),
                $this->cell($potongan > 0 ? 'Rp '.number_format($potongan, 0, ',', '.') : '-', $potongan, 'number'),
                $this->cell('Rp '.number_format($nominal, 0, ',', '.'), $nominal, 'number'),
                $this->cell('Rp '.number_format((int) $tagihan->paid, 0, ',', '.'), (int) $tagihan->paid, 'number'),
                $this->cell('Rp '.number_format($sisa, 0, ',', '.'), $sisa, 'number'),
                $this->cell('<span class="badge '.$tagihan->statusBadgeClass().'">'.e($tagihan->statusLabel()).'</span>', $tagihan->statusLabel(), 'text'),
                $this->cell(
                    '<span class="badge '.($tagihan->is_cicilan ? 'badge-info' : 'badge-neutral').'">'.e($tagihan->is_cicilan ? 'Ya' : 'Tidak').'</span>',
                    $tagihan->is_cicilan ? 'Ya' : 'Tidak',
                    'text'
                ),
                $methodLabel,
                $this->dateCell($tagihan->paid_dt),
                $this->dateCell($tagihan->due_date),
                $this->tagihanActionCell($tagihan, $canModify, $detail, app(PotonganTagihanService::class)),
            ];
        });
    }

    public function applyPotongan(Request $request, Tagihan $tagihan, PotonganTagihanService $potonganService): JsonResponse
    {
        $this->authorize('potongan-tagihan.update');

        try {
            $updated = DB::transaction(function () use ($tagihan, $potonganService, $request) {
                return $potonganService->applyToExisting($tagihan, $request->user());
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Potongan gagal diterapkan.',
                $e->errors(),
                422
            );
        }

        $potongan = (int) round((float) $updated->potongan_amount);
        $net = (int) round((float) $updated->amount);

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Potongan berhasil diterapkan: '.ActionMessage::rupiah($potongan).' · tagihan net '.ActionMessage::rupiah($net),
                ActionMessage::tagihan($updated)
            ),
            [
                'tagihan' => $updated,
                'pemakaian' => $this->formatPotonganPemakaian($updated),
            ]
        );
    }

    public function showPotongan(Tagihan $tagihan): JsonResponse
    {
        $this->authorize('potongan-tagihan.view');

        $tagihan->load([
            'potonganPemakaian.potonganSiswa.jenisPotongan',
            'siswa.kelas',
            'tahunAkademik',
        ]);

        return $this->jsonSuccess('OK', [
            'tagihan' => [
                'id' => $tagihan->id,
                'jenis' => $tagihan->jenis,
                'periode' => $tagihan->displayPeriode(),
                'amount_bruto' => (int) round($tagihan->displayBruto()),
                'potongan_amount' => (int) round((float) $tagihan->potongan_amount),
                'amount' => (int) round((float) $tagihan->amount),
                'siswa' => $tagihan->siswa?->name,
                'nis' => $tagihan->siswa?->nis,
            ],
            'pemakaian' => $this->formatPotonganPemakaian($tagihan),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formatPotonganPemakaian(Tagihan $tagihan): array
    {
        return $tagihan->potonganPemakaian
            ->sortBy('urutan')
            ->map(function ($row) {
                $step = (int) round((float) $row->potongan_amount);

                return [
                    'urutan' => $row->urutan,
                    'jenis_potongan' => $row->potonganSiswa?->jenisPotongan?->nama ?? '-',
                    'potongan_amount' => $step,
                    'potongan_label' => 'Rp '.number_format($step, 0, ',', '.'),
                    'amount_net' => (int) round((float) $row->amount_net),
                    'amount_net_label' => 'Rp '.number_format((int) round((float) $row->amount_net), 0, ',', '.'),
                ];
            })
            ->values()
            ->all();
    }

    public function update(UpdateTagihanRequest $request, Tagihan $tagihan, TagihanCicilanService $cicilanService): JsonResponse
    {
        if ($tagihan->isBillingLocked()) {
            return $this->jsonError($tagihan->billingLockMessage());
        }

        $data = $request->validated();
        $enableCicilan = (bool) ($data['enable_cicilan'] ?? false);
        $wasCicilan = (bool) $tagihan->is_cicilan;
        $nominal = (int) $data['amount'];
        $periode = $this->resolvePeriodeForTagihan(
            $data['periode'] ?? null,
            $tagihan->jenisTagihan,
            $tagihan->created_at ?? now(),
            $tagihan->tahunAkademik
        );

        $before = [
            'amount' => (string) (int) round($tagihan->displayAmount()),
            'periode' => (string) (int) $tagihan->periode,
            'due_date' => $tagihan->due_date?->toDateString(),
            'urutan' => $tagihan->urutan !== null ? (string) $tagihan->urutan : null,
        ];

        $nextUrutan = array_key_exists('urutan', $data) && $data['urutan'] !== null
            ? (int) $data['urutan']
            : null;
        $nextDueDate = $data['due_date'] ?? $tagihan->due_date?->toDateString();

        try {
            DB::transaction(function () use (
                $request,
                $tagihan,
                $cicilanService,
                $enableCicilan,
                $nominal,
                $periode,
                $nextUrutan,
                $nextDueDate,
                $before,
            ): void {
                if ($tagihan->is_cicilan) {
                    if (! $enableCicilan && ! $tagihan->canCancelCicilan()) {
                        throw ValidationException::withMessages([
                            'enable_cicilan' => 'Cicilan yang sudah berjalan tidak dapat dibatalkan.',
                        ]);
                    }

                    $paid = (int) round((float) $tagihan->paid);
                    $remaining = $nominal - $paid;

                    $tagihan->update([
                        'total_amount' => $nominal,
                        'amount' => max(0, $remaining),
                        'periode' => $periode,
                        'due_date' => $nextDueDate,
                        'urutan' => $nextUrutan,
                        'status' => $remaining <= 0
                            ? Tagihan::STATUS_PAID
                            : ($paid > 0 ? Tagihan::STATUS_CICILAN : Tagihan::STATUS_UNPAID),
                        'paid_dt' => $remaining <= 0 ? ($tagihan->paid_dt ?? now()) : null,
                    ]);

                    if (! $enableCicilan) {
                        $cicilanService->cancel($tagihan->fresh());
                    }
                } else {
                    $tagihan->update([
                        'amount' => $nominal,
                        'periode' => $periode,
                        'due_date' => $nextDueDate,
                        'urutan' => $nextUrutan,
                    ]);

                    if ($enableCicilan) {
                        $cicilanService->enable($tagihan->fresh());
                    }
                }

                $this->logTagihanEdits($request, $tagihan, $before, [
                    'amount' => (string) $nominal,
                    'periode' => (string) (int) $periode,
                    'due_date' => $nextDueDate,
                    'urutan' => $nextUrutan !== null ? (string) $nextUrutan : null,
                ]);
            });
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Tagihan gagal diperbarui.',
                $e->errors(),
                422
            );
        }

        $tagihan->refresh()->load(['siswa.kelas', 'tahunAkademik']);

        $subject = 'Tagihan berhasil diperbarui';
        if (! $wasCicilan && $tagihan->is_cicilan) {
            $subject = 'Tagihan berhasil diperbarui dan cicilan diaktifkan';
        } elseif ($wasCicilan && ! $tagihan->is_cicilan) {
            $subject = 'Tagihan berhasil diperbarui dan cicilan dinonaktifkan';
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject($subject, ActionMessage::tagihan($tagihan)),
            $tagihan
        );
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->has('amount')) {
            $request->merge([
                'amount' => str_replace('.', '', $request->input('amount')),
            ]);
        }

        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'tahun_akademik_id' => ['nullable', SoftDeleteRules::exists('tahun_akademik')],
            'jenis_tagihan_id' => ['required', SoftDeleteRules::exists('jenis_tagihan')],
            'amount' => 'required|numeric|min:1000',
            'periode' => TagihanPeriode::rules(false),
            'due_date' => 'nullable|date',
        ], [
            'amount.required' => 'Nominal tagihan wajib diisi.',
            'amount.numeric' => 'Nominal tagihan harus berupa angka.',
            'amount.min' => 'Nominal tagihan minimal :min.',
        ]);

        $jenisTagihan = JenisTagihan::query()->active()->find($data['jenis_tagihan_id']);
        if ($jenisTagihan === null) {
            throw ValidationException::withMessages([
                'jenis_tagihan_id' => 'Jenis tagihan tidak aktif atau tidak ditemukan.',
            ]);
        }
        $siswa = Siswa::query()->findOrFail($data['siswa_id']);
        $tahunAkademikId = $this->resolveTahunAkademikId($request, $data, $siswa);
        $tahunAkademik = TahunAkademik::query()->findOrFail($tahunAkademikId);
        $periode = $this->resolvePeriodeForTagihan($data['periode'] ?? null, $jenisTagihan, now(), $tahunAkademik);

        $tagihan = DB::transaction(function () use ($data, $siswa, $tahunAkademikId, $jenisTagihan, $periode, $request) {
            $tagihan = Tagihan::create([
                'sekolah_id' => $siswa->sekolah_id,
                'siswa_id' => $data['siswa_id'],
                'tahun_akademik_id' => $tahunAkademikId,
                'jenis_tagihan_id' => $jenisTagihan->id,
                'jenis' => $jenisTagihan->name,
                'amount' => $data['amount'],
                'periode' => $periode,
                'paid' => 0,
                'status' => Tagihan::STATUS_UNPAID,
                'due_date' => $data['due_date'] ?? now()->addDays($this->defaultDueDay())->toDateString(),
            ]);

            app(PotonganTagihanService::class)->autoApply($tagihan->fresh(['siswa']), $request->user());

            return $tagihan->fresh(['siswa.kelas', 'tahunAkademik']);
        });

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Tagihan '.ActionMessage::rupiah($data['amount']).' berhasil dibuat',
                ActionMessage::tagihan($tagihan)
            ),
            $tagihan,
            201
        );
    }

    public function generatePreview(Request $request): JsonResponse
    {
        $context = $this->resolveGenerateContext($request);

        return $this->jsonSuccess('OK', $context['summary']);
    }

    public function generate(Request $request): JsonResponse
    {
        $context = $this->resolveGenerateContext($request);
        $summary = $context['summary'];

        if ($summary['eligible'] === 0) {
            return $this->jsonError(
                $summary['skipped'] > 0
                    ? 'Semua siswa pada filter ini sudah memiliki tagihan untuk jenis, periode, dan tahun akademik tersebut.'
                    : 'Tidak ada siswa aktif yang memenuhi filter generate tagihan.'
            );
        }

        $created = 0;
        $potonganApplied = 0;
        DB::transaction(function () use ($context, &$created, &$potonganApplied): void {
            $potonganService = app(PotonganTagihanService::class);

            foreach ($context['eligible_students'] as $siswa) {
                $tagihan = Tagihan::create([
                    'sekolah_id' => $siswa->sekolah_id,
                    'siswa_id' => $siswa->id,
                    'tahun_akademik_id' => $context['tahun_akademik_id'],
                    'jenis_tagihan_id' => $context['jenis_tagihan']->id,
                    'jenis' => $context['jenis_tagihan']->name,
                    'amount' => $context['amount'],
                    'paid' => 0,
                    'status' => Tagihan::STATUS_UNPAID,
                    'periode' => $context['periode'],
                    'due_date' => $context['due_date'],
                ]);

                if ($potonganService->autoApply($tagihan->fresh(['siswa']))) {
                    $potonganApplied++;
                }

                $created++;
            }
        });

        $message = "{$created} tagihan {$context['jenis_tagihan']->name} periode "
            .TagihanPeriode::display($context['periode'])
            .' ('.ActionMessage::rupiah($context['amount']).') berhasil digenerate.';

        if ($potonganApplied > 0) {
            $message .= " Potongan otomatis diterapkan pada {$potonganApplied} tagihan.";
        }

        if ($summary['skipped'] > 0) {
            $message .= " {$summary['skipped']} siswa dilewati karena sudah memiliki tagihan jenis yang sama pada periode ini.";
        }

        return $this->jsonSuccess(ActionMessage::highlightVerbs($message));
    }

    public function destroy(Tagihan $tagihan): JsonResponse
    {
        if ($tagihan->isDeletionLocked()) {
            return $this->jsonError($tagihan->deletionLockMessage());
        }

        $detail = ActionMessage::tagihan($tagihan->load(['siswa.kelas', 'tahunAkademik']));
        $tagihan->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Tagihan berhasil dihapus', $detail));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveTahunAkademikId(Request $request, array $data, ?Siswa $siswa = null): int
    {
        if (! empty($data['tahun_akademik_id'])) {
            $tahun = TahunAkademik::query()->find($data['tahun_akademik_id']);
            if ($tahun === null) {
                throw ValidationException::withMessages([
                    'tahun_akademik_id' => 'Tahun akademik tidak ditemukan.',
                ]);
            }

            return (int) $tahun->id;
        }

        $id = TahunAkademik::query()
            ->where('is_active', true)
            ->value('id');

        if (! $id) {
            throw ValidationException::withMessages([
                'tahun_akademik_id' => 'Tahun akademik aktif tidak ditemukan.',
            ]);
        }

        return (int) $id;
    }

    /**
     * @return array{
     *     periode: int,
     *     tahun_akademik_id: int,
     *     amount: float,
     *     due_date: string,
     *     jenis_tagihan: JenisTagihan,
     *     eligible_students: \Illuminate\Support\Collection<int, Siswa>,
     *     summary: array<string, mixed>
     * }
     */
    private function resolveGenerateContext(Request $request): array
    {
        if ($request->has('amount')) {
            $request->merge([
                'amount' => str_replace('.', '', $request->input('amount')),
            ]);
        }

        $data = $request->validate([
            'tahun_akademik_id' => ['required', SoftDeleteRules::exists('tahun_akademik')],
            'jenis_tagihan_id' => ['required', SoftDeleteRules::exists('jenis_tagihan')],
            'periode' => TagihanPeriode::rules(false),
            'kelas_id' => ['nullable', SoftDeleteRules::exists('kelas')],
            'amount' => ['nullable', 'numeric', 'min:1000'],
            'due_date' => ['nullable', 'date'],
        ], [
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.min' => 'Nominal minimal :min.',
            'tahun_akademik_id.required' => 'Tahun akademik wajib dipilih.',
            'tahun_akademik_id.exists' => 'Tahun akademik tidak ditemukan.',
            'jenis_tagihan_id.required' => 'Jenis tagihan wajib dipilih.',
            'jenis_tagihan_id.exists' => 'Jenis tagihan tidak ditemukan.',
            'kelas_id.exists' => 'Kelas tidak ditemukan.',
            'periode.*' => 'Periode tidak valid.',
        ]);

        $tahunAkademik = TahunAkademik::query()->findOrFail($data['tahun_akademik_id']);
        $jenisTagihan = JenisTagihan::query()->active()->find($data['jenis_tagihan_id']);
        if ($jenisTagihan === null) {
            throw ValidationException::withMessages([
                'jenis_tagihan_id' => 'Jenis tagihan tidak aktif atau tidak ditemukan.',
            ]);
        }
        $periode = $this->resolvePeriodeForTagihan($data['periode'] ?? null, $jenisTagihan, now(), $tahunAkademik);

        $students = Siswa::query()
            ->active()
            ->when($request->filled('kelas_id'), fn ($query) => $query->where('kelas_id', $data['kelas_id']))
            ->orderBy('name')
            ->get();

        $eligibleStudents = collect();
        $skipped = 0;
        $potonganWillApply = 0;
        $potonganSkipped = 0;
        $potonganService = app(PotonganTagihanService::class);

        foreach ($students as $siswa) {
            if ($this->jenisExistsForStudent($siswa, $jenisTagihan, $periode, (int) $tahunAkademik->id)) {
                $skipped++;

                continue;
            }

            $eligibleStudents->push($siswa);

            if ($potonganService->previewWillApply($siswa, $jenisTagihan->id)) {
                $potonganWillApply++;
            } else {
                $potonganSkipped++;
            }
        }

        $amount = $request->filled('amount')
            ? (float) $data['amount']
            : ($jenisTagihan->default_amount
                ? (float) $jenisTagihan->default_amount
                : $this->defaultSppAmount());

        $dueDate = $data['due_date'] ?? now()->addDays($this->defaultDueDay())->toDateString();

        return [
            'periode' => $periode,
            'tahun_akademik_id' => (int) $tahunAkademik->id,
            'amount' => $amount,
            'due_date' => $dueDate,
            'jenis_tagihan' => $jenisTagihan,
            'eligible_students' => $eligibleStudents,
            'summary' => [
                'jenis_name' => $jenisTagihan->name,
                'tahun_akademik' => $tahunAkademik->name,
                'periode_label' => TagihanPeriode::display($periode),
                'total' => $students->count(),
                'eligible' => $eligibleStudents->count(),
                'skipped' => $skipped,
                'potongan_will_apply' => $potonganWillApply,
                'potongan_skipped' => $potonganSkipped,
                'all_exist' => $students->isNotEmpty() && $eligibleStudents->isEmpty(),
            ],
        ];
    }

    private function jenisExistsForStudent(Siswa $siswa, JenisTagihan $jenisTagihan, int $periode, int $tahunAkademikId): bool
    {
        return Tagihan::query()
            ->where('siswa_id', $siswa->id)
            ->where('tahun_akademik_id', $tahunAkademikId)
            ->where('periode', $periode)
            ->where(function ($query) use ($jenisTagihan) {
                $query->where('jenis_tagihan_id', $jenisTagihan->id)
                    ->orWhere('jenis', $jenisTagihan->name);
            })
            ->exists();
    }

    private function sppJenisTagihan(): ?JenisTagihan
    {
        return JenisTagihan::query()->active()->where('is_spp', true)->orderBy('id')->first();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, TModel>  $items
     * @return Collection<int, TModel>
     */
    private function uniqueCatalogByName(Collection $items): Collection
    {
        return $items
            ->unique(fn ($item) => mb_strtolower(trim((string) ($item->name ?? ''))))
            ->values();
    }

    private function defaultSppAmount(): float
    {
        $spp = $this->sppJenisTagihan();
        if ($spp?->default_amount) {
            return (float) $spp->default_amount;
        }

        $settings = $this->financeSettings();

        return (float) ($settings['spp_amount'] ?? 500000);
    }

    private function defaultDueDay(): int
    {
        $settings = $this->financeSettings();

        return (int) ($settings['due_day'] ?? 10);
    }

    private function financeSettings(): array
    {
        $path = 'settings/finance.json';
        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        return json_decode(Storage::disk('local')->get($path), true) ?? [];
    }

    /**
     * @param  Collection<int, JenisTagihan>  $jenisTagihanList
     * @return Collection<int, JenisTagihan>
     */
    private function sortJenisTagihanForSelect(Collection $jenisTagihanList): Collection
    {
        return $jenisTagihanList
            ->sortBy(function (JenisTagihan $jenisTagihan): array {
                $sppMonthOrder = $this->sppMonthlyOrder($jenisTagihan->name);
                $group = $sppMonthOrder !== null ? 0 : 1;

                return [
                    $group,
                    $sppMonthOrder ?? 99,
                    (int) ($jenisTagihan->sort_order ?? 0),
                    mb_strtoupper((string) $jenisTagihan->name),
                ];
            })
            ->values();
    }

    private function sppMonthlyOrder(?string $name): ?int
    {
        $label = strtoupper(trim((string) $name));
        if (! str_starts_with($label, 'SPP ')) {
            return null;
        }

        $monthName = trim(substr($label, 4));
        $order = [
            'JANUARI' => 1,
            'FEBRUARI' => 2,
            'MARET' => 3,
            'APRIL' => 4,
            'MEI' => 5,
            'JUNI' => 6,
            'JULI' => 7,
            'AGUSTUS' => 8,
            'SEPTEMBER' => 9,
            'OKTOBER' => 10,
            'NOVEMBER' => 11,
            'DESEMBER' => 12,
        ];

        return $order[$monthName] ?? null;
    }

    private function tagihanActionCell(Tagihan $tagihan, bool $canModify, array|string $detail, PotonganTagihanService $potonganService): array
    {
        $actions = [];
        $canDelete = ! $tagihan->isDeletionLocked();

        if ($tagihan->hasPotongan() || ($tagihan->potongan_pemakaian_count ?? 0) > 0) {
            $actions['potongan_detail'] = [
                'show_url' => route('admin.keuangan.tagihan.potongan.show', $tagihan),
                'modal_target' => 'tagihan-potongan-modal',
            ];
        }

        if ($potonganService->canApplyToExisting($tagihan)) {
            $actions['potongan_apply'] = [
                'apply_url' => route('admin.keuangan.tagihan.potongan.apply', $tagihan),
                'confirm_title' => 'Terapkan Potongan',
                'confirm_message' => 'Semua potongan aktif yang memenuhi syarat akan diterapkan sesuai urutan katalog.',
                'confirm_detail' => is_array($detail) ? $detail : [],
                'confirm_text' => 'Terapkan',
                'confirm_tone' => 'info',
            ];
        }

        if ($canModify) {
            $editFields = [
                'amount' => (int) round($tagihan->displayAmount()),
                'periode' => TagihanPeriode::toMonthInput($tagihan->periode),
                'due_date' => $tagihan->due_date?->format('Y-m-d') ?? '',
                'urutan' => $tagihan->urutan ?? '',
                'enable_cicilan' => (bool) $tagihan->is_cicilan,
                '_cicilan_in_progress' => $tagihan->isCicilanInProgress(),
                '_siswa_label' => ($tagihan->siswa?->name ?? '-').' ('.($tagihan->siswa?->nis ?? '-').')',
                '_jenis_label' => $tagihan->jenis,
                '_tahun_label' => $tagihan->tahunAkademik?->name ?? '-',
            ];

            $actions['edit'] = [
                'update_url' => route('admin.keuangan.tagihan.update', $tagihan),
                'form_target' => 'tagihan-edit-form',
                'modal_target' => 'tagihan-edit-modal',
                'record' => $editFields,
            ];
        }

        if ($canDelete) {
            $actions['delete'] = [
                'url' => route('admin.keuangan.tagihan.destroy', $tagihan),
                'confirm_title' => 'Konfirmasi Hapus',
                'confirm_message' => 'Apakah Anda yakin ingin menghapus tagihan ini?',
                'confirm_detail' => $detail,
            ];
        }

        if ($tagihan->is_cicilan && ! $tagihan->isInstallmentChild()) {
            $paidInstallments = (int) ($tagihan->cicilan_children_count ?? 0);

            $actions['cicilan_view'] = [
                'show_url' => route('admin.keuangan.tagihan.cicilan.show', $tagihan),
                'cancel_url' => $tagihan->canCancelCicilan()
                    ? route('admin.keuangan.tagihan.cicilan.destroy', $tagihan)
                    : null,
                'modal_target' => 'tagihan-cicilan-view-modal',
                'count' => $paidInstallments,
            ];
        }

        if ($actions === []) {
            return $this->cell('-', '-', 'action');
        }

        return $this->cell('', ['actions' => $actions], 'action');
    }

    /**
     * @param  array<string, string|null>  $before
     * @param  array<string, string|null>  $after
     */
    private function logTagihanEdits(Request $request, Tagihan $tagihan, array $before, array $after): void
    {
        foreach (array_keys($after) as $field) {
            $oldValue = $before[$field] ?? null;
            $newValue = $after[$field] ?? null;

            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            LogTagihanEdit::create([
                'tagihan_id' => $tagihan->id,
                'user_id' => $request->user()?->id,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    private function resolvePeriodeForTagihan(
        mixed $periodeInput,
        ?JenisTagihan $jenisTagihan,
        \DateTimeInterface $fallbackDate,
        ?TahunAkademik $tahunAkademik = null
    ): int
    {
        $periode = TagihanPeriode::normalize($periodeInput);
        $sppMonth = $this->monthFromJenisTagihanName($jenisTagihan);
        $isSpp = $sppMonth !== null || $jenisTagihan?->is_spp || strcasecmp((string) ($jenisTagihan?->name ?? ''), 'SPP') === 0;

        if ($sppMonth !== null) {
            $calendarYear = $this->resolveSppCalendarYear($sppMonth, $tahunAkademik, $fallbackDate);

            return TagihanPeriode::fromCalendarMonth($calendarYear, $sppMonth);
        }

        if ($periode === null && $isSpp) {
            throw ValidationException::withMessages([
                'periode' => 'Periode wajib diisi untuk tagihan SPP.',
            ]);
        }

        if ($periode !== null) {
            return $periode;
        }

        $timestamp = $fallbackDate->getTimestamp();

        return TagihanPeriode::fromCalendarMonth((int) date('Y', $timestamp), (int) date('n', $timestamp));
    }

    private function monthFromJenisTagihanName(?JenisTagihan $jenisTagihan): ?int
    {
        if (! $jenisTagihan) {
            return null;
        }

        $name = strtoupper(trim((string) $jenisTagihan->name));
        if ($name === '') {
            return null;
        }

        $monthMap = [
            'JANUARI' => 1,
            'FEBRUARI' => 2,
            'MARET' => 3,
            'APRIL' => 4,
            'MEI' => 5,
            'JUNI' => 6,
            'JULI' => 7,
            'AGUSTUS' => 8,
            'SEPTEMBER' => 9,
            'OKTOBER' => 10,
            'NOVEMBER' => 11,
            'DESEMBER' => 12,
        ];

        foreach ($monthMap as $monthName => $month) {
            if (str_contains($name, $monthName)) {
                return $month;
            }
        }

        return null;
    }

    private function resolveSppCalendarYear(int $month, ?TahunAkademik $tahunAkademik, \DateTimeInterface $fallbackDate): int
    {
        if ($tahunAkademik && preg_match('/^\d{4}\/\d{4}$/', $tahunAkademik->name) === 1) {
            [$startYear, $endYear] = array_map('intval', explode('/', $tahunAkademik->name));

            return $month >= 7 ? $startYear : $endYear;
        }

        $timestamp = $fallbackDate->getTimestamp();
        $fallbackYear = (int) date('Y', $timestamp);
        $fallbackMonth = (int) date('n', $timestamp);

        if ($month >= 7 && $fallbackMonth <= 6) {
            return $fallbackYear - 1;
        }

        if ($month <= 6 && $fallbackMonth >= 7) {
            return $fallbackYear + 1;
        }

        return $fallbackYear;
    }
}
