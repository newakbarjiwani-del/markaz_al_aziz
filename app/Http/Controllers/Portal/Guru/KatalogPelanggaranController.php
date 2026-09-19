<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Models\JenisPelanggaran;
use App\Support\AjaxSelect;
use App\Support\PelanggaranLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KatalogPelanggaranController extends Controller
{
    /**
     * Select2 AJAX lookup for the pelanggaran forms on the guru portal.
     * Mirrors the admin lookup (same catalog, guru-accessible route).
     */
    public function lookup(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        $query = JenisPelanggaran::query()
            ->active()
            ->ordered();

        if (mb_strlen($q) >= 1) {
            $query->where(function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                    ->orWhere('bidang', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");

                $level = PelanggaranLevel::normalize($q);
                if ($level !== null) {
                    $query->orWhere('level', $level);
                }
            });
        }

        $rows = $query->limit(20)->get();

        $results = $rows->map(fn (JenisPelanggaran $jenis) => [
            'id' => $jenis->id,
            'text' => "{$jenis->nama} — {$jenis->bidang} ({$jenis->levelLabel()})",
            'nama' => $jenis->nama,
            'point' => (int) $jenis->point,
            'level' => $jenis->level,
        ])->all();

        return AjaxSelect::respond($results);
    }

    public function lookupShow(JenisPelanggaran $jenisPelanggaran): JsonResponse
    {
        return response()->json([
            'id' => $jenisPelanggaran->id,
            'text' => "{$jenisPelanggaran->nama} — {$jenisPelanggaran->bidang} ({$jenisPelanggaran->levelLabel()})",
            'nama' => $jenisPelanggaran->nama,
            'point' => (int) $jenisPelanggaran->point,
            'level' => $jenisPelanggaran->level,
        ]);
    }
}
