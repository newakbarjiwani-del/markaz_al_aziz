<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Support\AjaxSelect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagihanLookupController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        if (mb_strlen($q) < 3) {
            return AjaxSelect::empty();
        }

        $onlyUnpaid = $request->boolean('unpaid', true);

        $tagihan = Tagihan::query()
            ->rootBill()
            ->with('siswa:id,nis,name')
            ->when($onlyUnpaid, fn ($query) => $query->unpaid())
            ->when($request->filled('siswa_id'), fn ($query) => $query->where('siswa_id', $request->integer('siswa_id')))
            ->where(function ($query) use ($q) {
                $query->where('jenis', 'like', "%{$q}%")
                    ->orWhere('periode', 'like', "%{$q}%")
                    ->orWhereHas('siswa', function ($siswaQuery) use ($q) {
                        $siswaQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('nis', 'like', "%{$q}%");
                    });

                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q)
                        ->orWhere('periode', (int) $q);
                }
            })
            ->orderByDesc('due_date')
            ->limit(20)
            ->get();

        $results = $tagihan->map(fn (Tagihan $row) => [
            'id' => $row->id,
            'text' => $this->label($row),
        ])->all();

        return AjaxSelect::respond($results);
    }

    public function show(Tagihan $tagihan): JsonResponse
    {
        $tagihan->load('siswa:id,nis,name');

        return response()->json([
            'id' => $tagihan->id,
            'text' => $this->label($tagihan),
        ]);
    }

    private function label(Tagihan $tagihan): string
    {
        $siswa = $tagihan->siswa;
        $siswaLabel = $siswa
            ? trim($siswa->nis.' — '.$siswa->name)
            : 'Siswa #'.$tagihan->siswa_id;

        return sprintf(
            '%s · %s · %s (Sisa: Rp %s)',
            $siswaLabel,
            $tagihan->jenis,
            $tagihan->displayPeriode(),
            number_format($tagihan->remaining(), 0, ',', '.')
        );
    }
}
