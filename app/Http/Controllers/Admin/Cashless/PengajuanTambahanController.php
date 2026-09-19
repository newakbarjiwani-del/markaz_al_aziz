<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Dompet;
use App\Models\PengajuanUangSaku;
use App\Models\Siswa;
use App\Models\TransaksiCashless;
use App\Support\ActionMessage;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PengajuanTambahanController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.dompet-digital.pengajuan-tambahan', [
            'title' => 'Pengajuan Tambahan',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = PengajuanUangSaku::query()->with('siswa');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status', 'reason'],
            'orderable' => ['amount', 'status', 'created_at'],
        ], function (PengajuanUangSaku $row) {
            $aksi = $row->status === 'pending'
                ? $this->cell(
                    '<button type="button" data-approve-url="'.route('admin.dompet-digital.pengajuan-tambahan.approve', $row).'" class="btn-primary btn-sm">Setujui</button>',
                    null,
                    'action'
                )
                : '-';

            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $this->cell('Rp '.number_format($row->amount, 0, ',', '.'), (int) $row->amount, 'number'),
                \Illuminate\Support\Str::limit($row->reason ?? '-', 40),
                ucfirst($row->status),
                $aksi,
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'amount' => 'required|numeric|min:1000',
            'reason' => 'nullable|string|max:500',
        ]);

        $pengajuan = PengajuanUangSaku::create(array_merge($data, ['status' => 'pending']));
        $siswa = Siswa::with('kelas')->findOrFail($data['siswa_id']);

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Pengajuan tambahan uang saku '.ActionMessage::rupiah($data['amount']).' berhasil dikirim',
                ActionMessage::siswa($siswa)
            ),
            $pengajuan,
            201
        );
    }

    public function approve(PengajuanUangSaku $pengajuan): JsonResponse
    {
        try {
            DB::transaction(function () use ($pengajuan): void {
                $locked = PengajuanUangSaku::query()
                    ->whereKey($pengajuan->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== 'pending') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'pengajuan' => 'Pengajuan sudah diproses.',
                    ]);
                }

                $dompet = Dompet::query()
                    ->where('siswa_id', $locked->siswa_id)
                    ->lockForUpdate()
                    ->first();

                if ($dompet === null) {
                    Dompet::create(['siswa_id' => $locked->siswa_id]);
                    $dompet = Dompet::query()
                        ->where('siswa_id', $locked->siswa_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $dompet->increment('saldo_us', $locked->amount);

                TransaksiCashless::create([
                    'siswa_id' => $locked->siswa_id,
                    'type' => 'topup',
                    'category' => 'pengajuan',
                    'amount' => $locked->amount,
                    'wallet' => 'us',
                    'description' => 'Pengajuan tambahan disetujui',
                ]);

                $locked->update(['status' => 'disetujui']);
                $pengajuan->setRawAttributes($locked->getAttributes());
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Pengajuan gagal diproses.',
                $e->errors(),
                422
            );
        }

        $pengajuan->refresh()->load('siswa.kelas');

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Pengajuan disetujui · saldo '.ActionMessage::rupiah($pengajuan->amount).' ditambahkan',
                ActionMessage::siswa($pengajuan->siswa)
            )
        );
    }
}
