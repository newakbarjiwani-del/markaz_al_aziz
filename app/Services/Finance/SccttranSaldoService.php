<?php

namespace App\Services\Finance;

use App\Models\Sccttran;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SccttranSaldoService
{
    public function balanceForSiswa(int $siswaId): int
    {
        return $this->balancesForSiswaIds([$siswaId])[$siswaId] ?? 0;
    }

    /**
     * @param  list<int>  $siswaIds
     * @return array<int, int>
     */
    public function balancesForSiswaIds(array $siswaIds): array
    {
        if ($siswaIds === []) {
            return [];
        }

        $rows = Sccttran::query()
            ->whereIn('CUSTID', $siswaIds)
            ->selectRaw('CUSTID, COALESCE(SUM(KREDIT), 0) - COALESCE(SUM(DEBET), 0) AS balance')
            ->groupBy('CUSTID')
            ->pluck('balance', 'CUSTID');

        $map = [];
        foreach ($siswaIds as $id) {
            $map[$id] = (int) ($rows[$id] ?? 0);
        }

        return $map;
    }

    /**
     * @return array{total_saldo: int, siswa_bersaldo: int, rata_rata: int}
     */
    public function globalStats(?int $sekolahId = null): array
    {
        $agg = $this->ledgerAggregateSubquery();

        if ($sekolahId !== null) {
            $agg->whereIn('CUSTID', Siswa::query()->where('sekolah_id', $sekolahId)->select('id'));
        }

        $row = DB::query()
            ->fromSub($agg, 'balances')
            ->selectRaw('COALESCE(SUM(balance), 0) AS total_saldo')
            ->selectRaw('SUM(CASE WHEN balance > 0 THEN 1 ELSE 0 END) AS siswa_bersaldo')
            ->selectRaw('COALESCE(AVG(CASE WHEN balance > 0 THEN balance END), 0) AS rata_rata')
            ->first();

        return [
            'total_saldo' => (int) ($row->total_saldo ?? 0),
            'siswa_bersaldo' => (int) ($row->siswa_bersaldo ?? 0),
            'rata_rata' => (int) ($row->rata_rata ?? 0),
        ];
    }

    public function siswaWithBalanceQuery(bool $onlyPositiveBalance = false): Builder
    {
        $agg = $this->ledgerAggregateSubquery();

        $query = Siswa::query()
            ->with('kelas')
            ->select('siswa.*');

        if ($onlyPositiveBalance) {
            $query
                ->joinSub($agg, 'ledger_agg', 'ledger_agg.CUSTID', '=', 'siswa.id')
                ->where('ledger_agg.balance', '>', 0)
                ->addSelect([
                    'ledger_agg.balance as finance_balance',
                    'ledger_agg.last_trx_at as last_trx_at',
                ]);
        } else {
            $query
                ->leftJoinSub($agg, 'ledger_agg', 'ledger_agg.CUSTID', '=', 'siswa.id')
                ->addSelect([
                    DB::raw('COALESCE(ledger_agg.balance, 0) as finance_balance'),
                    'ledger_agg.last_trx_at as last_trx_at',
                ]);
        }

        return $query;
    }

    public function transactionsQuery(int $siswaId, Request $request): Builder
    {
        $query = Sccttran::query()
            ->where('CUSTID', $siswaId)
            ->when($request->filled('metode'), fn (Builder $q) => $q->where('METODE', $request->string('metode')->toString()))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('TRXDATE', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('TRXDATE', '<=', $request->date_to));

        return $query;
    }

    /**
     * @param  list<int>  $siswaIds
     * @return array{saldo: int, bulan_ini_kredit: int, bulan_ini_debet: int, total_transaksi: int}
     */
    public function statsForSiswaIds(array $siswaIds): array
    {
        if ($siswaIds === []) {
            return [
                'saldo' => 0,
                'bulan_ini_kredit' => 0,
                'bulan_ini_debet' => 0,
                'total_transaksi' => 0,
            ];
        }

        $saldo = array_sum($this->balancesForSiswaIds($siswaIds));

        $baseQuery = Sccttran::query()->whereIn('CUSTID', $siswaIds);
        $monthQuery = (clone $baseQuery)->where('TRXDATE', '>=', now()->startOfMonth());

        return [
            'saldo' => $saldo,
            'bulan_ini_kredit' => (int) (clone $monthQuery)->sum('KREDIT'),
            'bulan_ini_debet' => (int) (clone $monthQuery)->sum('DEBET'),
            'total_transaksi' => (int) (clone $baseQuery)->count(),
        ];
    }

    /**
     * @param  list<int>  $siswaIds
     */
    public function transactionsQueryForSiswaIds(array $siswaIds, Request $request): Builder
    {
        $query = Sccttran::query()
            ->with('siswa.kelas')
            ->whereIn('CUSTID', $siswaIds)
            ->when($request->filled('siswa_id'), fn (Builder $q) => $q->where('CUSTID', (int) $request->siswa_id))
            ->when($request->filled('metode'), fn (Builder $q) => $q->where('METODE', $request->string('metode')->toString()))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('TRXDATE', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('TRXDATE', '<=', $request->date_to));

        return $query;
    }

    private function ledgerAggregateSubquery(): QueryBuilder
    {
        return DB::table('sccttran')
            ->selectRaw('CUSTID, COALESCE(SUM(KREDIT), 0) - COALESCE(SUM(DEBET), 0) AS balance, MAX(TRXDATE) AS last_trx_at')
            ->groupBy('CUSTID');
    }
}
