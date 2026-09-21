<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\PortalAccess;
use App\Models\QrisPayment;
use App\Models\Siswa;
use App\Services\Finance\Qris\QrisGenerateService;
use App\Services\Finance\Qris\QrisStatusService;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class QrisPaymentController extends Controller
{
    use DataTableTrait;
    use PortalAccess;

    public function store(Request $request, QrisGenerateService $generateService, QrisStatusService $statusService): JsonResponse
    {
        $childIds = $this->ortuChildIds();
        abort_if($childIds === [], 403, 'Tidak ada data anak yang terhubung.');

        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'tagihan_ids' => ['required', 'array', 'min:1', 'max:30'],
            'tagihan_ids.*' => ['required', SoftDeleteRules::exists('tagihan')],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $siswaId = (int) $data['siswa_id'];
        abort_unless(in_array($siswaId, $childIds, true), 403, 'Anak tidak terhubung ke akun ini.');

        $siswa = Siswa::query()->findOrFail($siswaId);

        try {
            $result = $generateService->generate(
                $siswa,
                $data['tagihan_ids'],
                auth()->id(),
                (string) ($data['description'] ?? 'Pembayaran tagihan portal ortu'),
            );
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Validasi gagal.',
                $e->errors(),
                422
            );
        } catch (Throwable $e) {
            return $this->jsonError($e->getMessage(), [], 502);
        }

        return $this->jsonSuccess('QRIS berhasil dibuat. Silakan scan untuk membayar.', [
            'qris' => $statusService->formatPublic($result['qris']),
        ], 201);
    }

    public function show(QrisPayment $qrisPayment, QrisStatusService $statusService): JsonResponse
    {
        $this->authorizeOrtuQris($qrisPayment);
        $qrisPayment->load(['items.tagihan', 'siswa', 'pembayaran']);

        return $this->jsonSuccess('OK', [
            'qris' => $statusService->formatPublic($qrisPayment),
        ]);
    }

    public function check(QrisPayment $qrisPayment, QrisStatusService $statusService): JsonResponse
    {
        $this->authorizeOrtuQris($qrisPayment);

        try {
            $result = $statusService->check($qrisPayment, settleIfPaid: true);
        } catch (RuntimeException $e) {
            return $this->jsonError($e->getMessage(), [], 502);
        }

        return $this->jsonSuccess(
            $result['settled'] ? 'Pembayaran QRIS berhasil. Tagihan sudah lunas.' : 'Status QRIS diperbarui.',
            [
                'qris' => $statusService->formatPublic($result['qris']),
                'settled' => $result['settled'],
            ]
        );
    }

    private function authorizeOrtuQris(QrisPayment $qris): void
    {
        $childIds = $this->ortuChildIds();
        abort_unless(in_array((int) $qris->siswa_id, $childIds, true), 403);
    }
}
