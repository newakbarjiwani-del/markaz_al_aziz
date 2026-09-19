<?php

namespace App\Services\Finance;

use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembayaranCancellationService
{
    public const MANUAL_CASH = '1140000';

    public const MANUAL_BMI = '1140001';

    public const MANUAL_SALDO = '1140002';

    public const MANUAL_TRANSFER = '1140003';

    public const CANCELLABLE_METHODS = [
        self::MANUAL_CASH,
        self::MANUAL_BMI,
        self::MANUAL_SALDO,
        self::MANUAL_TRANSFER,
    ];

    public function __construct(
        private readonly SaldoKeuanganService $saldoKeuanganService,
        private readonly SccttranLogger $sccttranLogger,
        private readonly PembayaranCancellationLogger $cancellationLogger,
    ) {}

    public function canCancel(Pembayaran $payment): bool
    {
        return in_array($payment->method, self::CANCELLABLE_METHODS, true);
    }

    public function cancel(Pembayaran $payment, ?Request $request = null): Pembayaran
    {
        if (! $this->canCancel($payment)) {
            throw ValidationException::withMessages([
                'pembayaran' => 'Pembayaran ini tidak dapat dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($payment, $request) {
            $payment = Pembayaran::query()
                ->lockForUpdate()
                ->with(['details.tagihan', 'siswa'])
                ->findOrFail($payment->id);

            if (! $this->canCancel($payment)) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Pembayaran ini tidak dapat dibatalkan.',
                ]);
            }

            foreach ($payment->details as $detail) {
                $this->reverseDetail($payment, $detail);
            }

            if ($payment->method === self::MANUAL_SALDO) {
                $this->reverseSaldoPayment($payment);
            }

            if ($request !== null) {
                $this->cancellationLogger->log($request, $payment);
            }

            $payment->details()->delete();
            $payment->delete();

            return $payment;
        });
    }

    private function reverseDetail(Pembayaran $payment, PembayaranDetail $detail): void
    {
        $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($detail->tagihan_id);
        $amount = (int) round((float) $detail->amount);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'pembayaran' => 'Detail pembayaran tidak valid.',
            ]);
        }

        if ($tagihan->is_cicilan && ! $tagihan->isInstallmentChild()) {
            $this->reverseInstallmentDetail($payment, $detail, $tagihan, $amount);

            return;
        }

        if ($tagihan->isInstallmentChild()) {
            throw ValidationException::withMessages([
                'pembayaran' => 'Pembayaran pada baris cicilan tidak dapat dibatalkan dari halaman ini.',
            ]);
        }

        $this->reverseRegularDetail($tagihan, $amount);
    }

    private function reverseInstallmentDetail(
        Pembayaran $payment,
        PembayaranDetail $detail,
        Tagihan $parent,
        int $amount,
    ): void {
        $child = $this->resolveInstallmentChild($parent, $payment, $detail, $amount);

        if ($child === null) {
            throw ValidationException::withMessages([
                'pembayaran' => 'Data cicilan untuk pembayaran ini tidak ditemukan.',
            ]);
        }

        $child->delete();

        $parent->increment('amount', $amount);
        $parent->decrement('paid', $amount);
        $parent->refresh();

        if ((float) $parent->paid <= 0) {
            $parent->update([
                'status' => Tagihan::STATUS_UNPAID,
                'paid_dt' => null,
                'paid_dt_actual' => null,
                'reference' => null,
                'fidbank' => null,
                'user_id' => null,
                'sccttran_id' => null,
            ]);

            return;
        }

        $parent->update([
            'status' => Tagihan::STATUS_CICILAN,
            'paid_dt' => null,
            'paid_dt_actual' => null,
            'reference' => null,
            'fidbank' => null,
            'user_id' => null,
            'sccttran_id' => null,
        ]);
    }

    private function resolveInstallmentChild(
        Tagihan $parent,
        Pembayaran $payment,
        PembayaranDetail $detail,
        int $amount,
    ): ?Tagihan {
        $query = Tagihan::query()
            ->where('parent_id', $parent->id)
            ->where('amount', $amount)
            ->where('reference', $payment->reference);

        if ($payment->paid_dt !== null) {
            $query->where('paid_dt', $payment->paid_dt);
        }

        $child = $query->orderByDesc('cicilan_ke')->first();

        if ($child !== null) {
            return $child;
        }

        return $parent->cicilanChildren()
            ->where('amount', $amount)
            ->orderByDesc('cicilan_ke')
            ->first();
    }

    private function reverseRegularDetail(Tagihan $tagihan, int $amount): void
    {
        if ((float) $tagihan->paid < $amount) {
            throw ValidationException::withMessages([
                'pembayaran' => 'Tagihan tidak memiliki pembayaran yang cukup untuk dibatalkan.',
            ]);
        }

        $tagihan->decrement('paid', $amount);
        $tagihan->refresh();
        $tagihan->syncPaymentStatus();

        if (! $tagihan->isPaid()) {
            $tagihan->update([
                'reference' => null,
                'fidbank' => null,
                'user_id' => null,
                'sccttran_id' => null,
            ]);
        }
    }

    private function reverseSaldoPayment(Pembayaran $payment): void
    {
        $siswa = $payment->siswa;
        if ($siswa === null) {
            throw ValidationException::withMessages([
                'pembayaran' => 'Siswa pada pembayaran ini tidak ditemukan.',
            ]);
        }

        $total = (int) round((float) $payment->details->sum('amount'));
        if ($total <= 0) {
            return;
        }

        $saldo = $this->saldoKeuanganService->forSiswa($siswa, lock: true);
        $this->saldoKeuanganService->credit($saldo, $total);

        $payment->details->each(function (PembayaranDetail $detail) use ($payment, $siswa): void {
            $amount = (int) round((float) $detail->amount);
            if ($amount <= 0) {
                return;
            }

            $this->sccttranLogger->jurnalSaldo($siswa->id, $amount, 'tambah', [
                'trxdate' => now(),
                'refno' => 'VOID-'.$payment->reference,
            ]);
        });
    }
}
