<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\Siswa;
use App\Models\TahfidzProgress;
use App\Models\TahfidzSurah;
use App\Services\TahfidzProgressService;
use App\Support\TahfidzProgressStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TahfidzController extends Controller
{
    use PortalAccess;

    public function __construct(private readonly TahfidzProgressService $progressService) {}

    public function index(): View
    {
        $this->linkedGuru();

        $progress = TahfidzProgress::query()
            ->with(['siswa', 'surah'])
            ->orderByDesc('last_reviewed_at')
            ->limit(50)
            ->get();

        return view('portal.guru.tahfidz.index', [
            'title' => 'Tahfidz',
            'progress' => $progress,
            'surahs' => TahfidzSurah::query()->orderBy('number')->get(),
            'statuses' => TahfidzProgressStatus::labels(),
        ]);
    }

    public function storeProgress(Request $request): RedirectResponse
    {
        $this->linkedGuru();

        $data = $request->validate([
            'siswa_id' => ['required', 'exists:siswa,id'],
            'surah_id' => ['required', Rule::exists('tahfidz_surah', 'id')],
            'ayah_from' => ['required', 'integer', 'min:1'],
            'ayah_to' => ['required', 'integer', 'min:1', 'gte:ayah_from'],
            'status' => ['required', Rule::in(TahfidzProgressStatus::values())],
            'note' => ['nullable', 'string', 'max:2000'],
            'verified' => ['nullable', 'boolean'],
        ]);

        $siswa = Siswa::query()->findOrFail($data['siswa_id']);

        $this->progressService->upsert([
            'siswa_id' => $siswa->id,
            'sekolah_id' => $siswa->sekolah_id,
            'surah_id' => (int) $data['surah_id'],
            'ayah_from' => (int) $data['ayah_from'],
            'ayah_to' => (int) $data['ayah_to'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'source' => 'guru',
            'verified' => $request->boolean('verified'),
            'actor_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('portal.guru.tahfidz.index')
            ->with('success', 'Progress siswa diperbarui.');
    }
}
