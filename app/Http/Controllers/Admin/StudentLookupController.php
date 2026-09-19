<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Support\AjaxSelect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentLookupController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        if (mb_strlen($q) < 3) {
            return AjaxSelect::empty();
        }

        $studentsQuery = Siswa::query()
            ->with('kelas:id,name');

        \App\Support\AdminSchoolScope::apply($studentsQuery);

        if ($request->filled('sekolah_id') && \App\Support\AdminSchoolScope::operatorSekolahId() === null) {
            $studentsQuery->where('sekolah_id', $request->integer('sekolah_id'));
        }

        $students = $studentsQuery
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = \App\Support\SiswaStatus::normalize($request->input('status'));
                if ($status !== null) {
                    $query->where('status', $status);
                }
            })
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%");
            })
            ->when($request->filled('kelas_id'), fn ($query) => $query->where('kelas_id', $request->kelas_id))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'nis', 'name', 'kelas_id', 'sekolah_id', 'status']);

        $legacy = $students->map(fn (Siswa $siswa) => $this->mapStudent($siswa))->values()->all();
        $results = collect($legacy)->map(fn (array $item) => [
            'id' => $item['id'],
            'text' => $item['label'],
            'sekolah_id' => $item['sekolah_id'],
        ])->all();

        return AjaxSelect::respond($results, $legacy);
    }

    public function show(Siswa $siswa): JsonResponse
    {
        $siswa->load('kelas:id,name');
        $mapped = $this->mapStudent($siswa);

        return response()->json([
            'id' => $mapped['id'],
            'text' => $mapped['label'],
            'data' => $mapped,
        ]);
    }

    /** @return array{id: int, nis: string, name: string, kelas: ?string, label: string} */
    private function mapStudent(Siswa $siswa): array
    {
        return [
            'id' => $siswa->id,
            'nis' => $siswa->nis,
            'name' => $siswa->name,
            'sekolah_id' => $siswa->sekolah_id,
            'kelas' => $siswa->kelas?->name,
            'label' => trim($siswa->nis.' — '.$siswa->name.($siswa->kelas ? ' ('.$siswa->kelas->name.')' : '')),
        ];
    }
}
