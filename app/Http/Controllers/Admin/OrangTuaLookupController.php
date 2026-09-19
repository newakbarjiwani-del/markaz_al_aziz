<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrangTua;
use App\Support\AjaxSelect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrangTuaLookupController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        if (mb_strlen($q) < 3) {
            return AjaxSelect::empty();
        }

        $parents = OrangTua::query()
            ->withCount('siswa')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->where(function ($query) use ($q) {
                $query->where('nama_ayah', 'like', "%{$q}%")
                    ->orWhere('nama_ibu', 'like', "%{$q}%")
                    ->orWhere('nama_wali', 'like', "%{$q}%")
                    ->orWhere('telepon_ayah', 'like', "%{$q}%")
                    ->orWhere('telepon_ibu', 'like', "%{$q}%")
                    ->orWhere('telepon_wali', 'like', "%{$q}%");
            })
            ->orderBy('nama_ayah')
            ->limit(20)
            ->get();

        $legacy = $parents->map(fn (OrangTua $orangTua) => $this->mapOrangTua($orangTua))->values()->all();
        $results = collect($legacy)->map(fn (array $item) => [
            'id' => $item['id'],
            'text' => $item['label'],
        ])->all();

        return AjaxSelect::respond($results, $legacy);
    }

    public function show(OrangTua $orangTua): JsonResponse
    {
        $orangTua->loadCount('siswa');
        $mapped = $this->mapOrangTua($orangTua);

        return response()->json([
            'id' => $mapped['id'],
            'text' => $mapped['label'],
            'data' => $mapped,
        ]);
    }

    /** @return array{id: int, label: string, phone: ?string, children_count: int} */
    private function mapOrangTua(OrangTua $orangTua): array
    {
        $phone = $orangTua->primaryPhone();
        $suffix = $phone ? ' · '.$phone : '';
        if (($orangTua->siswa_count ?? 0) > 0) {
            $suffix .= ' · '.$orangTua->siswa_count.' anak';
        }

        return [
            'id' => $orangTua->id,
            'label' => $orangTua->displayName().$suffix,
            'phone' => $phone,
            'children_count' => (int) ($orangTua->siswa_count ?? 0),
        ];
    }
}
