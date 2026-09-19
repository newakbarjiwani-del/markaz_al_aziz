<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Support\AjaxSelect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherLookupController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        if (mb_strlen($q) < 3) {
            return AjaxSelect::empty();
        }

        $gurus = Guru::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'nip', 'name', 'jabatan', 'status']);

        $legacy = $gurus->map(fn (Guru $guru) => $this->mapGuru($guru))->values()->all();
        $results = collect($legacy)->map(fn (array $item) => [
            'id' => $item['id'],
            'text' => $item['label'],
        ])->all();

        return AjaxSelect::respond($results, $legacy);
    }

    public function show(Guru $guru): JsonResponse
    {
        $mapped = $this->mapGuru($guru);

        return response()->json([
            'id' => $mapped['id'],
            'text' => $mapped['label'],
            'data' => $mapped,
        ]);
    }

    /** @return array{id: int, nip: string, name: string, jabatan: ?string, label: string} */
    private function mapGuru(Guru $guru): array
    {
        return [
            'id' => $guru->id,
            'nip' => $guru->nip,
            'name' => $guru->name,
            'jabatan' => $guru->jabatan,
            'label' => trim($guru->nip.' — '.$guru->name.($guru->jabatan ? ' ('.$guru->jabatan.')' : '')),
        ];
    }
}
