<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Tagihan;
use App\Services\Finance\TagihanCicilanService;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TagihanCicilanController extends Controller
{
    use DataTableTrait;

    public function show(Tagihan $tagihan, TagihanCicilanService $cicilanService): JsonResponse
    {
        if ($tagihan->isInstallmentChild()) {
            $tagihan = $tagihan->parent ?? $tagihan;
        }

        $tagihan->load(['siswa.kelas', 'tahunAkademik', 'cicilanChildren']);

        return $this->jsonSuccess('OK', [
            'tagihan' => [
                'id' => $tagihan->id,
                'jenis' => $tagihan->jenis,
                'amount' => (float) $tagihan->displayAmount(),
                'paid' => (float) $tagihan->paid,
                'remaining' => $tagihan->remaining(),
                'is_cicilan' => (bool) $tagihan->is_cicilan,
                'status' => $tagihan->status,
                'status_label' => $tagihan->statusLabel(),
                'siswa' => $tagihan->siswa?->name,
                'nis' => $tagihan->siswa?->nis,
            ],
            'schedule' => $cicilanService->scheduleSummary($tagihan),
            'next_payable' => $tagihan->is_cicilan ? $cicilanService->payableAmount($tagihan) : null,
            'can_cancel' => $tagihan->canCancelCicilan(),
        ]);
    }

    public function destroy(Tagihan $tagihan, TagihanCicilanService $cicilanService): JsonResponse
    {
        if (! $tagihan->canCancelCicilan()) {
            return $this->jsonError('Cicilan yang sudah berjalan tidak dapat dibatalkan.', [], 422);
        }

        try {
            $cicilanService->cancel($tagihan);
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Cicilan gagal dibatalkan.',
                $e->errors(),
                422
            );
        }

        $tagihan->load(['siswa.kelas', 'tahunAkademik']);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Cicilan berhasil dibatalkan', ActionMessage::tagihan($tagihan))
        );
    }
}
