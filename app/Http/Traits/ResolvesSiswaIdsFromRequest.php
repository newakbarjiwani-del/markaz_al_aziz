<?php

namespace App\Http\Traits;

use App\Models\Siswa;
use Illuminate\Foundation\Http\FormRequest;

trait ResolvesSiswaIdsFromRequest
{
    /**
     * @return list<int>
     */
    protected function siswaIdsFromRequest(FormRequest $request): array
    {
        if ($request->isMethod('POST') && $request->has('siswa_ids')) {
            $ids = array_map('intval', (array) $request->input('siswa_ids', []));

            return array_values(array_unique(array_filter($ids, fn (int $id) => $id > 0)));
        }

        return [$request->integer('siswa_id')];
    }

    /**
     * @return list<Siswa>
     */
    protected function siswaListFromRequest(FormRequest $request): array
    {
        $ids = $this->siswaIdsFromRequest($request);

        return Siswa::query()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Siswa $siswa) => array_search($siswa->id, $ids, true))
            ->values()
            ->all();
    }
}
