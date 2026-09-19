<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\AlokasiUangSaku;
use App\Models\Siswa;
use App\Support\ActionMessage;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlokasiUangSakuController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.dompet-digital.alokasi-uang-saku', [
            'title' => 'Alokasi Uang Saku',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = AlokasiUangSaku::query()->with('siswa');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['period', 'status'],
            'orderable' => ['amount', 'status', 'created_at'],
        ], function (AlokasiUangSaku $row) {
            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                'Rp '.number_format($row->amount, 0, ',', '.'),
                $row->period ?? '-',
                ucfirst($row->status),
                $row->created_at,
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'amount' => 'required|numeric|min:1000',
            'period' => 'nullable|string|max:50',
        ]);

        $siswa = Siswa::with('kelas')->findOrFail($data['siswa_id']);
        try {
            $siswa->assertCanTransact();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Siswa tidak aktif.',
                $e->errors(),
                422
            );
        }

        $alokasi = AlokasiUangSaku::create(array_merge($data, [
            'status' => 'aktif',
            'period' => $data['period'] ?? now()->format('Y-m'),
        ]));

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Alokasi uang saku '.ActionMessage::rupiah($data['amount']).' berhasil dibuat',
                ActionMessage::siswa($siswa)
            ),
            $alokasi,
            201
        );
    }
}
