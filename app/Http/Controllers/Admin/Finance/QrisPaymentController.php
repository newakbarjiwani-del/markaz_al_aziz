<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\QrisPayment;
use App\Models\Siswa;
use App\Services\Finance\Qris\QrisGenerateService;
use App\Services\Finance\Qris\QrisStatusService;
use App\Support\AdminSchoolScope;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class QrisPaymentController extends Controller
{
    use DataTableTrait;

    public function store(Request $request, QrisGenerateService $generateService, QrisStatusService $statusService): JsonResponse
    {
        $this->authorize('finance.create');

        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'tagihan_ids' => ['required', 'array', 'min:1', 'max:30'],
            'tagihan_ids.*' => ['required', SoftDeleteRules::exists('tagihan')],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $siswa = Siswa::query()->findOrFail((int) $data['siswa_id']);
        $this->authorizeSiswa($siswa);

        try {
            $result = $generateService->generate(
                $siswa,
                $data['tagihan_ids'],
                auth()->id(),
                (string) ($data['description'] ?? ''),
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

        return $this->jsonSuccess('QRIS berhasil dibuat.', [
            'qris' => $statusService->formatPublic($result['qris']),
        ], 201);
    }

    public function show(QrisPayment $qrisPayment, QrisStatusService $statusService): JsonResponse
    {
        $this->authorize('finance.view');
        $this->authorizeQris($qrisPayment);

        $qrisPayment->load(['items.tagihan', 'siswa', 'pembayaran']);

        return $this->jsonSuccess('OK', [
            'qris' => $statusService->formatPublic($qrisPayment),
        ]);
    }

    public function check(QrisPayment $qrisPayment, QrisStatusService $statusService): JsonResponse
    {
        $this->authorize('finance.view');
        $this->authorizeQris($qrisPayment);

        try {
            $result = $statusService->check($qrisPayment, settleIfPaid: true);
        } catch (RuntimeException $e) {
            return $this->jsonError($e->getMessage(), [], 502);
        }

        return $this->jsonSuccess(
            $result['settled'] ? 'Pembayaran QRIS terdeteksi dan tagihan dilunasi.' : 'Status QRIS diperbarui.',
            [
                'qris' => $statusService->formatPublic($result['qris']),
                'settled' => $result['settled'],
            ]
        );
    }

    private function authorizeSiswa(Siswa $siswa): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $siswa->sekolah_id !== $scoped) {
            abort(403);
        }
    }

    private function authorizeQris(QrisPayment $qris): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $qris->sekolah_id !== $scoped) {
            abort(403);
        }
    }
}
