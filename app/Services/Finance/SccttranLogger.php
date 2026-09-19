<?php

namespace App\Services\Finance;

use App\Models\Sccttran;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class SccttranLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function topUp(int $siswaId, int $amount, array $context): Sccttran
    {
        return Sccttran::create([
            'CUSTID' => $siswaId,
            'METODE' => 'TOP UP',
            'TRXDATE' => $this->parseTrxDate($context['trxdate'] ?? null),
            'KREDIT' => $amount,
            'DEBET' => 0,
            'NOREFF' => $this->normalizeRef($context['refno'] ?? null),
            'FIDBANK' => $this->normalizeBank($context['kodebank'] ?? null),
            'KDCHANNEL' => $this->normalizeChannel($context['channelid'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function fromInvoice(int $siswaId, int $amount, array $context): Sccttran
    {
        return Sccttran::create([
            'CUSTID' => $siswaId,
            'METODE' => 'FROM INVOICE',
            'TRXDATE' => $this->parseTrxDate($context['trxdate'] ?? null),
            'KREDIT' => 0,
            'DEBET' => $amount,
            'NOREFF' => $this->normalizeRef($context['refno'] ?? null),
            'FIDBANK' => $this->normalizeBank($context['kodebank'] ?? null),
            'KDCHANNEL' => $this->normalizeChannel($context['channelid'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function fromSaldo(int $siswaId, int $amount, array $context): Sccttran
    {
        return Sccttran::create([
            'CUSTID' => $siswaId,
            'METODE' => 'FROM SALDO',
            'TRXDATE' => $this->parseTrxDate($context['trxdate'] ?? null),
            'KREDIT' => 0,
            'DEBET' => $amount,
            'NOREFF' => $this->normalizeRef($context['refno'] ?? null),
            'FIDBANK' => $this->normalizeBank($context['kodebank'] ?? null),
            'KDCHANNEL' => $this->normalizeChannel($context['channelid'] ?? null),
        ]);
    }

    public function topUpRefExists(string $refno): bool
    {
        return Sccttran::query()
            ->where('METODE', 'TOP UP')
            ->where('NOREFF', $this->normalizeRef($refno))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function jurnalSaldo(int $siswaId, int $amount, string $type, array $context = []): Sccttran
    {
        $isCredit = $type === 'tambah';

        return Sccttran::create([
            'CUSTID' => $siswaId,
            'METODE' => 'JURNAL SALDO',
            'TRXDATE' => $this->parseTrxDate($context['trxdate'] ?? null),
            'KREDIT' => $isCredit ? $amount : 0,
            'DEBET' => $isCredit ? 0 : $amount,
            'NOREFF' => $this->normalizeRef($context['refno'] ?? null),
            'FIDBANK' => null,
            'KDCHANNEL' => null,
        ]);
    }

    private function parseTrxDate(mixed $value): Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && filled($value)) {
            return Carbon::parse($value);
        }

        return now();
    }

    private function normalizeRef(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return substr((string) $value, 0, 20);
    }

    private function normalizeBank(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return substr((string) $value, 0, 10);
    }

    private function normalizeChannel(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }
}
