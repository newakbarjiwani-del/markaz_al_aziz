<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cashless\WithdrawPendapatanKantinRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\SccttranCashless;
use App\Models\User;
use App\Services\Cashless\PendapatanKantinWithdrawService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PendapatanKantinController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function __construct(
        private readonly PendapatanKantinWithdrawService $withdrawService,
    ) {}

    public function index(): View
    {
        $this->authorize('cashless.view');

        return view('admin.dompet-digital.pendapatan-kantin', [
            'title' => 'Pendapatan Kantin',
            'schools' => AdminSchoolScope::schools(),
            'showSekolahFilter' => AdminSchoolScope::operatorSekolahId() === null,
            'stats' => $this->stats(),
            'canWithdraw' => auth()->user()?->can('cashless.create') ?? false,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('cashless.view');

        $query = $this->kantinOperatorsQuery($request);
        $this->applyBelanjaAggregates($query, $request);
        $this->applyOutstandingAggregates($query);

        $canWithdraw = $request->user()?->can('cashless.create') ?? false;

        return $this->datatableResponse($request, $query->orderBy('name'), [
            'searchable' => ['name', 'username', 'sekolah.name'],
            'orderable' => ['name', 'username', 'name', 'pendapatan', 'transaksi_count', 'name', 'name'],
        ], function (User $operator) use ($canWithdraw) {
            $pendapatan = (float) ($operator->pendapatan ?? 0);
            $transaksiCount = (int) ($operator->transaksi_count ?? 0);
            $outstanding = max(
                0,
                (int) ($operator->omzet_all ?? 0) - (int) ($operator->total_penarikan ?? 0)
            );

            $actions = '<div class="action-group">'
                .'<button type="button" class="btn-action btn-action-view"'
                .' data-pendapatan-kantin-detail'
                .' data-user-id="'.$operator->id.'"'
                .' title="Detail Transaksi"'
                .' aria-label="Detail Transaksi">'
                .'<i class="ti ti-eye"></i><span class="btn-action-label">Detail</span></button>';

            if ($canWithdraw && $outstanding > 0) {
                $actions .= '<button type="button" class="btn-action btn-action-withdraw"'
                    .' data-pendapatan-kantin-withdraw'
                    .' data-user-id="'.$operator->id.'"'
                    .' data-user-name="'.e($operator->name).'"'
                    .' data-outstanding="'.$outstanding.'"'
                    .' title="Tarik Tunai"'
                    .' aria-label="Tarik Tunai">'
                    .'<i class="ti ti-cash"></i><span class="btn-action-label">Tarik</span></button>';
            }

            $actions .= '</div>';

            return [
                e($operator->name),
                e($operator->username),
                e($operator->sekolah?->name ?? 'Semua Sekolah'),
                $this->cell('Rp '.number_format($pendapatan, 0, ',', '.'), $pendapatan, 'number'),
                $this->cell(number_format($transaksiCount, 0, ',', '.'), $transaksiCount, 'number'),
                $this->cell('Rp '.number_format($outstanding, 0, ',', '.'), $outstanding, 'number'),
                $this->cell($actions, null, 'action'),
            ];
        });
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('cashless.view');
        $this->ensureKantinOperator($user);

        return $this->jsonSuccess('Detail operator kantin.', [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'sekolah' => $user->sekolah?->name ?? 'Semua Sekolah',
            'status' => $user->statusLabel(),
            'outstanding' => $this->withdrawService->outstandingFor($user),
        ]);
    }

    public function transactions(Request $request, User $user): JsonResponse
    {
        $this->authorize('cashless.view');
        $this->ensureKantinOperator($user);

        $query = $this->belanjaQuery($request)
            ->where('user_id', $user->id)
            ->with(['siswa.kelas']);

        return $this->datatableResponse($request, $query->orderByDesc('TRXDATE')->orderByDesc('id'), [
            'searchable' => ['description', 'wallet', 'siswa.name', 'siswa.nis'],
            'orderable' => ['TRXDATE', 'TRXDATE', 'TRXDATE', 'wallet', 'DEBET', 'description'],
        ], function (SccttranCashless $row) {
            return [
                $this->dateCell($row->TRXDATE),
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $this->walletLabel($row->wallet),
                $this->cell('Rp '.number_format((int) $row->DEBET, 0, ',', '.'), (int) $row->DEBET, 'number'),
                $row->description ?: '-',
            ];
        });
    }

    public function withdraw(WithdrawPendapatanKantinRequest $request, User $user): JsonResponse
    {
        $this->ensureKantinOperator($user);

        try {
            $penarikan = $this->withdrawService->withdraw(
                $user,
                $request->user(),
                $request->validated(),
            );
        } catch (InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage());
        }

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Penarikan pendapatan kantin berhasil',
                ActionMessage::user($user).' · '.ActionMessage::rupiah($penarikan->amount)
                .' · Ref '.$penarikan->noreff
            ),
            [
                'id' => $penarikan->id,
                'noreff' => $penarikan->noreff,
                'amount' => $penarikan->amount,
                'outstanding' => $this->withdrawService->outstandingFor($user->fresh()),
            ],
            201
        );
    }

    /** @return array{hari_ini: float, bulan_ini: float, total_operator: int} */
    private function stats(): array
    {
        $operators = $this->kantinOperatorsQuery(request())->pluck('id');

        $base = SccttranCashless::query()
            ->where('METODE', 'BELANJA')
            ->where('DEBET', '>', 0)
            ->whereIn('user_id', $operators);

        return [
            'hari_ini' => (float) (clone $base)->whereDate('TRXDATE', today())->sum('DEBET'),
            'bulan_ini' => (float) (clone $base)->where('TRXDATE', '>=', now()->startOfMonth())->sum('DEBET'),
            'total_operator' => $operators->count(),
        ];
    }

    private function kantinOperatorsQuery(Request $request): Builder
    {
        $scopedSekolahId = AdminSchoolScope::operatorSekolahId($request->user());

        return User::query()
            ->role('kantin')
            ->with('sekolah')
            ->when($scopedSekolahId !== null, fn (Builder $q) => $q->where('sekolah_id', $scopedSekolahId))
            ->when(
                $scopedSekolahId === null && $request->filled('sekolah_id'),
                fn (Builder $q) => $q->where('sekolah_id', $request->integer('sekolah_id'))
            )
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $like = '%'.trim($request->string('q')->toString()).'%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like);
                });
            });
    }

    private function applyBelanjaAggregates(Builder $query, Request $request): void
    {
        $dateFrom = $request->filled('date_from') ? $request->string('date_from')->toString() : null;
        $dateTo = $request->filled('date_to') ? $request->string('date_to')->toString() : null;

        $scope = function (Builder $relation) use ($dateFrom, $dateTo): void {
            $relation->where('METODE', 'BELANJA')->where('DEBET', '>', 0);

            if ($dateFrom) {
                $relation->whereDate('TRXDATE', '>=', $dateFrom);
            }

            if ($dateTo) {
                $relation->whereDate('TRXDATE', '<=', $dateTo);
            }
        };

        $query
            ->withSum(['cashlessTransactions as pendapatan' => $scope], 'DEBET')
            ->withCount(['cashlessTransactions as transaksi_count' => $scope]);
    }

    private function applyOutstandingAggregates(Builder $query): void
    {
        $query
            ->withSum([
                'cashlessTransactions as omzet_all' => fn (Builder $relation) => $relation
                    ->where('METODE', 'BELANJA')
                    ->where('DEBET', '>', 0),
            ], 'DEBET')
            ->withSum('penarikanPendapatanKantin as total_penarikan', 'amount');
    }

    private function belanjaQuery(Request $request): Builder
    {
        $query = SccttranCashless::query()
            ->where('METODE', 'BELANJA')
            ->where('DEBET', '>', 0);

        $this->applyDateRange($query, $request, 'TRXDATE');

        return $query;
    }

    private function ensureKantinOperator(User $user): void
    {
        abort_unless($user->hasRole('kantin'), 404);

        $scopedSekolahId = AdminSchoolScope::operatorSekolahId();
        if ($scopedSekolahId !== null && (int) $user->sekolah_id !== $scopedSekolahId) {
            abort(404);
        }
    }

    private function walletLabel(?string $wallet): string
    {
        return match ($wallet) {
            'us' => 'Uang Saku',
            'kantin' => 'Kantin',
            'tabungan' => 'Tabungan',
            default => $wallet ? strtoupper($wallet) : '-',
        };
    }
}
