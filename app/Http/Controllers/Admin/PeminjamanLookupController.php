<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeminjamanBuku;
use App\Support\AdminSchoolScope;
use App\Support\AjaxSelect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\DisplayDate;

class PeminjamanLookupController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        if (mb_strlen($q) < 3) {
            return AjaxSelect::empty();
        }

        $loans = $this->baseQuery()
            ->where(function ($query) use ($q) {
                $query->whereHas('peminjaman.siswa', function ($siswaQuery) use ($q) {
                    $siswaQuery->where('name', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                })->orWhereHas('buku', function ($bukuQuery) use ($q) {
                    $bukuQuery->where('judul', 'like', "%{$q}%");
                });

                if (ctype_digit($q)) {
                    $query->orWhere('peminjaman_buku.id', (int) $q);
                }
            })
            ->orderByRaw('(SELECT due_date FROM peminjaman WHERE peminjaman.id = peminjaman_buku.peminjaman_id LIMIT 1)')
            ->limit(20)
            ->get();

        $results = $loans->map(fn (PeminjamanBuku $loan) => [
            'id' => $loan->id,
            'text' => $this->label($loan),
        ])->all();

        return AjaxSelect::respond($results);
    }

    public function show(PeminjamanBuku $peminjaman): JsonResponse
    {
        $peminjaman->load(['peminjaman.siswa:id,nis,name,sekolah_id', 'buku:id,judul,sekolah_id']);

        if ($this->scopedSekolahId() !== null) {
            $sekolahId   = $this->scopedSekolahId();
            $loanSekolah = $peminjaman->peminjaman?->siswa?->sekolah_id ?? $peminjaman->buku?->sekolah_id;

            if ($loanSekolah !== $sekolahId) {
                abort(404);
            }
        }

        return response()->json([
            'id' => $peminjaman->id,
            'text' => $this->label($peminjaman),
        ]);
    }

    protected function scopedSekolahId(): ?int
    {
        return AdminSchoolScope::operatorSekolahId();
    }

    protected function baseQuery(): Builder
    {
        return PeminjamanBuku::query()
            ->with(['peminjaman.siswa:id,nis,name,sekolah_id', 'buku:id,judul,sekolah_id'])
            ->where('peminjaman_buku.status', 'dipinjam')
            ->when(
                $this->scopedSekolahId(),
                fn (Builder $query, int $sekolahId) => $query->whereHas(
                    'peminjaman.siswa',
                    fn (Builder $siswaQuery) => $siswaQuery->where('sekolah_id', $sekolahId)
                )
            );
    }

    private function label(PeminjamanBuku $loan): string
    {
        $due = DisplayDate::date($loan->due_date, '-');

        return sprintf(
            '%s — %s (jatuh tempo %s)',
            $loan->peminjaman?->siswa?->name ?? 'Siswa #'.$loan->peminjaman?->siswa_id,
            $loan->buku?->judul ?? 'Buku #'.$loan->buku_id,
            $due
        );
    }
}
