<?php

namespace App\Support;

use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Tagihan dashboard totals use root bills only so cicilan children
 * are not double-counted against their parent.
 */
final class TagihanDashboard
{
    public static function billedTotal(): float
    {
        return (float) Tagihan::query()
            ->rootBill()
            ->sum(DB::raw('COALESCE(total_amount, amount)'));
    }

    public static function paidTotal(): float
    {
        return (float) Tagihan::query()->rootBill()->sum('paid');
    }

    public static function outstandingTotal(): float
    {
        return (float) Tagihan::query()->rootBill()->unpaid()->sum('amount');
    }

    public static function unpaidCount(): int
    {
        return Tagihan::query()->rootBill()->unpaid()->count();
    }

    public static function overdueCount(null|\DateTimeInterface|string $asOf = null): int
    {
        return Tagihan::query()
            ->rootBill()
            ->unpaid()
            ->whereDate('due_date', '<', $asOf ?? today())
            ->count();
    }

    /**
     * @return Collection<string, int>
     */
    public static function countsByStatusLabel(): Collection
    {
        return Tagihan::query()
            ->rootBill()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($total, $status) => [Tagihan::statusLabelFor((int) $status) => (int) $total]);
    }

    /**
     * @return Collection<string, float>
     */
    public static function billedByJenis(): Collection
    {
        return Tagihan::query()
            ->rootBill()
            ->select('jenis', DB::raw('SUM(COALESCE(total_amount, amount)) as total'))
            ->groupBy('jenis')
            ->orderByDesc('total')
            ->pluck('total', 'jenis')
            ->map(fn ($total) => (float) $total);
    }
}
