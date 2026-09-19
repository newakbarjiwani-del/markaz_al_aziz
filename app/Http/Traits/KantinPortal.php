<?php

namespace App\Http\Traits;

use App\Models\Dompet;
use App\Models\MenuKantin;
use App\Models\SccttranCashless;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;

trait KantinPortal
{
    protected function kantinSekolahId(): ?int
    {
        $sekolahId = auth()->user()?->sekolah_id;

        return $sekolahId !== null ? (int) $sekolahId : null;
    }

    protected function siswaOutsideKantinScope(Siswa $siswa): bool
    {
        $sekolahId = $this->kantinSekolahId();

        return $sekolahId !== null && (int) $siswa->sekolah_id !== $sekolahId;
    }

    protected function kantinTransaksiQuery(): Builder
    {
        return SccttranCashless::query()
            ->with(['siswa.kelas', 'user'])
            ->when($this->kantinSekolahId(), function (Builder $query, int $sekolahId) {
                $query->whereHas('siswa', fn (Builder $q) => $q->where('sekolah_id', $sekolahId));
            });
    }

    /**
     * Belanja rows created by kantin POS (any cashless wallet: us → kantin → tabungan).
     *
     * @param  bool  $onlyCurrentOperator  When true, limit to the logged-in kantin operator's POS sales.
     */
    protected function kantinBelanjaQuery(bool $onlyCurrentOperator = false): Builder
    {
        return $this->kantinTransaksiQuery()
            ->where('METODE', 'BELANJA')
            ->where('DEBET', '>', 0)
            ->when($onlyCurrentOperator, fn (Builder $query) => $query->where('user_id', auth()->id()));
    }

    protected function cashlessWalletLabel(?string $wallet): string
    {
        return match ($wallet) {
            'us' => 'Uang Saku',
            'kantin' => 'Kantin',
            'tabungan' => 'Tabungan',
            default => $wallet ? strtoupper($wallet) : '-',
        };
    }

    protected function kantinMenuQuery(): Builder
    {
        return MenuKantin::query()
            ->where('is_active', true)
            ->when($this->kantinSekolahId(), fn (Builder $query, int $sekolahId) => $query->where('sekolah_id', $sekolahId));
    }

    protected function kantinDashboardStats(): array
    {
        $sekolahId = $this->kantinSekolahId();
        $todayStart = now()->startOfDay();

        $belanjaToday = $this->kantinBelanjaQuery(onlyCurrentOperator: true)
            ->where('TRXDATE', '>=', $todayStart);

        $dompetQuery = Dompet::query()->when($sekolahId, function (Builder $query, int $id) {
            $query->whereHas('siswa', fn (Builder $q) => $q->where('sekolah_id', $id));
        });

        return [
            'transaksi_hari_ini' => (clone $belanjaToday)->count(),
            'omzet_hari_ini' => (float) (clone $belanjaToday)->sum('DEBET'),
            'total_dompet_kantin' => (float) (clone $dompetQuery)->sum('saldo_kantin'),
            'dompet_aktif' => (clone $dompetQuery)
                ->whereHas('siswa', fn (Builder $q) => $q->where('status', Siswa::STATUS_ACTIVE))
                ->where('saldo_kantin', '>', 0)
                ->count(),
            'menu_aktif' => $this->kantinMenuQuery()->count(),
        ];
    }
}
