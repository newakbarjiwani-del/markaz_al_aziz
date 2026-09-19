<?php

namespace App\Services\Finance;

use App\Models\Siswa;
use App\Support\VirtualAccountNumber;
use Illuminate\Database\Eloquent\Collection;

class FinanceVaResolver
{
    public function resolveSiswa(string $vano): ?Siswa
    {
        $digits = preg_replace('/\D/', '', $vano) ?? '';

        if (strlen($digits) < VirtualAccountNumber::PREFIX_LENGTH + 1) {
            return null;
        }

        $suffix = substr($digits, VirtualAccountNumber::PREFIX_LENGTH, VirtualAccountNumber::VA_NIS_LENGTH);
        $shortNis = ltrim($suffix, '0');

        if ($shortNis === '') {
            return null;
        }

        $matches = $this->findCandidates($shortNis, $suffix);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * Active siswa whose NIS produces the same VA suffix as the given NIS.
     *
     * @return Collection<int, Siswa>
     */
    public function studentsSharingVaSuffix(?string $nis, ?int $ignoreSiswaId = null): Collection
    {
        $suffix = VirtualAccountNumber::nisSuffix($nis);

        if ($suffix === null) {
            return new Collection;
        }

        $shortNis = ltrim($suffix, '0');
        if ($shortNis === '') {
            $shortNis = '0';
        }

        $matches = $this->findCandidates($shortNis, $suffix);

        if ($ignoreSiswaId !== null && $ignoreSiswaId > 0) {
            $matches = $matches->where('id', '!=', $ignoreSiswaId)->values();
        }

        return $matches;
    }

    /**
     * Soft warning when another active siswa already shares this VA suffix.
     */
    public function vaSuffixCollisionWarning(?string $nis, ?int $ignoreSiswaId = null): ?string
    {
        $others = $this->studentsSharingVaSuffix($nis, $ignoreSiswaId);

        if ($others->isEmpty()) {
            return null;
        }

        $suffix = VirtualAccountNumber::nisSuffix($nis);
        $vano = VirtualAccountNumber::fromNis($nis);
        $names = $others
            ->take(5)
            ->map(fn (Siswa $s) => $s->name.' (NIS '.$s->nis.')')
            ->implode(', ');

        $extra = $others->count() > 5 ? ' dan '.($others->count() - 5).' lainnya' : '';

        return 'Peringatan: 10 digit terakhir NIS ('.$suffix.') menghasilkan No. VA '
            .$vano.' yang sama dengan '.$names.$extra
            .'. Pembayaran VA online bisa gagal jika lebih dari satu siswa aktif memakai 10 digit terakhir NIS yang sama.';
    }

    /**
     * @return Collection<int, Siswa>
     */
    private function findCandidates(string $shortNis, string $suffix): Collection
    {
        $length = VirtualAccountNumber::VA_NIS_LENGTH;
        $driver = Siswa::query()->getConnection()->getDriverName();

        $suffixMatch = $driver === 'sqlite'
            ? "substr(nis, -{$length}) = ?"
            : "RIGHT(nis, {$length}) = ?";

        return Siswa::query()
            ->where(function ($query) use ($shortNis, $suffix, $suffixMatch) {
                $query->where('nis', $shortNis)
                    ->orWhere('nis', $suffix)
                    ->orWhereRaw($suffixMatch, [$suffix]);
            })
            ->get();
    }
}
