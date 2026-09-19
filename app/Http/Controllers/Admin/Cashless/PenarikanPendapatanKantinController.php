<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\PenarikanPendapatanKantin;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenarikanPendapatanKantinController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        $this->authorize('cashless.view');

        return view('admin.dompet-digital.penarikan-pendapatan-kantin', [
            'title' => 'Riwayat Penarikan Pendapatan Kantin',
            'schools' => AdminSchoolScope::schools(),
            'showSekolahFilter' => AdminSchoolScope::operatorSekolahId() === null,
            'canVoid' => auth()->user()?->can('cashless.delete') ?? false,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('cashless.view');

        $query = $this->scopedQuery($request)
            ->with(['kantinUser', 'sekolah', 'settledByUser']);

        return $this->datatableResponse($request, $query->orderByDesc('settled_at')->orderByDesc('id'), [
            'searchable' => ['noreff', 'description', 'kantinUser.name', 'kantinUser.username', 'settledByUser.name'],
            'orderable' => ['settled_at', 'noreff', 'amount', 'method', 'settled_at'],
        ], function (PenarikanPendapatanKantin $row) {
            $actions = [];
            if (auth()->user()?->can('cashless.delete')) {
                $actions['delete'] = [
                    'url' => route('admin.dompet-digital.pendapatan-kantin.penarikan.destroy', $row),
                    'confirm_title' => 'Batalkan Penarikan',
                    'confirm_message' => 'Batalkan penarikan ini? Sisa belum ditarik operator akan bertambah kembali.',
                    'confirm_detail' => [
                        ['label' => 'No. Ref', 'value' => $row->noreff],
                        ['label' => 'Operator', 'value' => $row->kantinUser?->name ?? '-'],
                        ['label' => 'Nominal', 'value' => 'Rp '.number_format($row->amount, 0, ',', '.')],
                    ],
                    'confirm_tone' => 'danger',
                ];
            }

            return [
                $this->dateCell($row->settled_at),
                e($row->noreff),
                e($row->kantinUser?->name ?? '-'),
                e($row->sekolah?->name ?? 'Semua Sekolah'),
                $this->cell('Rp '.number_format($row->amount, 0, ',', '.'), $row->amount, 'number'),
                e($row->method),
                e($row->settledByUser?->name ?? '-'),
                e($row->description ?: '-'),
                $actions !== []
                    ? $this->cell('', ['actions' => $actions], 'action')
                    : '-',
            ];
        });
    }

    public function destroy(PenarikanPendapatanKantin $penarikan): JsonResponse
    {
        $this->authorize('cashless.delete');
        $this->ensureAccessible($penarikan);

        $penarikan->loadMissing('kantinUser');
        $detail = ActionMessage::user($penarikan->kantinUser).' · '.ActionMessage::rupiah($penarikan->amount);
        $penarikan->delete();

        return $this->jsonSuccess(
            ActionMessage::withSubject('Penarikan pendapatan kantin dibatalkan', $detail)
        );
    }

    private function scopedQuery(Request $request): Builder
    {
        $scopedSekolahId = AdminSchoolScope::operatorSekolahId($request->user());

        return PenarikanPendapatanKantin::query()
            ->when($scopedSekolahId !== null, fn (Builder $q) => $q->where('sekolah_id', $scopedSekolahId))
            ->when(
                $scopedSekolahId === null && $request->filled('sekolah_id'),
                fn (Builder $q) => $q->where('sekolah_id', $request->integer('sekolah_id'))
            )
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $like = '%'.trim($request->string('q')->toString()).'%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('noreff', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('kantinUser', function (Builder $user) use ($like) {
                            $user->where('name', 'like', $like)
                                ->orWhere('username', 'like', $like);
                        });
                });
            });
    }

    private function ensureAccessible(PenarikanPendapatanKantin $penarikan): void
    {
        $scopedSekolahId = AdminSchoolScope::operatorSekolahId();
        if ($scopedSekolahId !== null && (int) $penarikan->sekolah_id !== $scopedSekolahId) {
            abort(404);
        }
    }
}
