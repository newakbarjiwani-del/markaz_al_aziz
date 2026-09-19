<?php

namespace App\Services\Cashless;

use App\Models\PenarikanPendapatanKantin;
use App\Models\SccttranCashless;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PendapatanKantinWithdrawService
{
    public function outstandingFor(User $operator): int
    {
        $omzet = (int) SccttranCashless::query()
            ->where('user_id', $operator->id)
            ->where('METODE', 'BELANJA')
            ->where('DEBET', '>', 0)
            ->sum('DEBET');

        $settled = (int) PenarikanPendapatanKantin::query()
            ->where('kantin_user_id', $operator->id)
            ->sum('amount');

        return max(0, $omzet - $settled);
    }

    /**
     * @param  array{amount: int, description?: string|null}  $payload
     */
    public function withdraw(User $operator, User $settledBy, array $payload): PenarikanPendapatanKantin
    {
        if (! $operator->hasRole('kantin')) {
            throw new InvalidArgumentException('Hanya operator kantin yang dapat ditarik pendapatannya.');
        }

        $amount = (int) ($payload['amount'] ?? 0);
        if ($amount < 1) {
            throw new InvalidArgumentException('Nominal penarikan minimal Rp 1.');
        }

        return DB::transaction(function () use ($operator, $settledBy, $payload, $amount) {
            // Lock prior settlements for this operator to serialize concurrent withdraws.
            PenarikanPendapatanKantin::query()
                ->where('kantin_user_id', $operator->id)
                ->lockForUpdate()
                ->get(['id']);

            $outstanding = $this->outstandingFor($operator);
            if ($amount > $outstanding) {
                throw new InvalidArgumentException(
                    'Nominal melebihi sisa belum ditarik (Rp '.number_format($outstanding, 0, ',', '.').').'
                );
            }

            $description = isset($payload['description'])
                ? trim((string) $payload['description'])
                : null;

            return PenarikanPendapatanKantin::create([
                'kantin_user_id' => $operator->id,
                'sekolah_id' => $operator->sekolah_id,
                'amount' => $amount,
                'method' => PenarikanPendapatanKantin::METHOD_CASH,
                'noreff' => $this->uniqueNoreff(),
                'description' => $description !== '' ? $description : null,
                'settled_by' => $settledBy->id,
                'settled_at' => now(),
            ]);
        });
    }

    public function void(PenarikanPendapatanKantin $penarikan): void
    {
        $penarikan->delete();
    }

    private function uniqueNoreff(): string
    {
        do {
            $noreff = 'PK'.now()->format('YmdHis').Str::upper(Str::random(4));
        } while (PenarikanPendapatanKantin::withTrashed()->where('noreff', $noreff)->exists());

        return $noreff;
    }
}
