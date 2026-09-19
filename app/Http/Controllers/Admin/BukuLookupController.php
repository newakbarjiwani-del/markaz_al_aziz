<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Support\AdminSchoolScope;
use App\Support\AjaxSelect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BukuLookupController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        if (mb_strlen($q) < 3) {
            return AjaxSelect::empty();
        }

        $books = $this->baseQuery()
            ->where(function (Builder $query) use ($q) {
                $query->where('judul', 'like', "%{$q}%")
                    ->orWhere('pengarang', 'like', "%{$q}%")
                    ->orWhere('penerbit', 'like', "%{$q}%")
                    ->orWhere('kode_buku', 'like', "%{$q}%")
                    ->orWhere('isbn', 'like', "%{$q}%");

                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
            })
            ->orderBy('judul')
            ->limit(20)
            ->get(['id', 'judul', 'pengarang', 'penerbit', 'isbn', 'kode_buku', 'tersedia']);

        $results = $books->map(fn (Buku $buku) => [
            'id' => $buku->id,
            'text' => $this->label($buku),
        ])->all();

        return AjaxSelect::respond($results);
    }

    public function show(Buku $buku): JsonResponse
    {
        if ($this->scopedSekolahId() !== null
            && $buku->sekolah_id !== null
            && $buku->sekolah_id !== $this->scopedSekolahId()) {
            abort(404);
        }

        if ($buku->tersedia <= 0) {
            abort(404);
        }

        return response()->json([
            'id' => $buku->id,
            'text' => $this->label($buku),
        ]);
    }

    protected function scopedSekolahId(): ?int
    {
        return null;
    }

    protected function baseQuery(): Builder
    {
        return Buku::query()
            ->where('tersedia', '>', 0)
            ->when(
                $this->scopedSekolahId(),
                fn (Builder $query, int $sekolahId) => $query->where(function (Builder $inner) use ($sekolahId) {
                    $inner->whereNull('sekolah_id')->orWhere('sekolah_id', $sekolahId);
                })
            );
    }

    private function label(Buku $buku): string
    {
        $parts = array_filter([
            $buku->judul,
            $buku->pengarang ? 'oleh '.$buku->pengarang : null,
            'tersedia '.$buku->tersedia,
        ]);

        return implode(' — ', $parts);
    }
}
