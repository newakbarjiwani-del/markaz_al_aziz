<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\Booklet;
use App\Models\Guru;
use Illuminate\View\View;

class BookletController extends Controller
{
    use PortalAccess;

    public function index(): View
    {
        $sekolahId = $this->resolveViewerSekolahId();

        $booklets = Booklet::query()
            ->published()
            ->visibleForSekolah($sekolahId)
            ->withCount('pages')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return view('portal.booklet.index', [
            'title' => 'Booklet Sekolah',
            'booklets' => $booklets,
            'showRouteName' => $this->showRouteName(),
        ]);
    }

    public function show(Booklet $booklet): View
    {
        abort_unless($booklet->is_published, 404);

        $sekolahId = $this->resolveViewerSekolahId();
        if ($sekolahId !== null && $booklet->sekolah_id !== null && (int) $booklet->sekolah_id !== $sekolahId) {
            abort(404);
        }

        $booklet->load('pages');

        return view('portal.booklet.show', [
            'title' => $booklet->title,
            'booklet' => $booklet,
            'indexRouteName' => $this->indexRouteName(),
        ]);
    }

    private function resolveViewerSekolahId(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->hasRole('siswa') && $user->siswa_id) {
            $siswa = $this->linkedSiswa();

            return $siswa->sekolah_id ? (int) $siswa->sekolah_id : null;
        }

        if ($user->hasRole('orang_tua') && $user->orang_tua_id) {
            $child = $this->ortuChildren()->first();

            return $child?->sekolah_id ? (int) $child->sekolah_id : null;
        }

        if ($user->hasRole('guru') && $user->guru_id) {
            $guruSekolahId = Guru::withoutGlobalScopes()
                ->whereKey($user->guru_id)
                ->value('sekolah_id');

            return $guruSekolahId !== null ? (int) $guruSekolahId : null;
        }

        return filled($user->sekolah_id) ? (int) $user->sekolah_id : null;
    }

    private function indexRouteName(): string
    {
        return match (true) {
            auth()->user()?->hasRole('guru') => 'portal.guru.booklet.index',
            auth()->user()?->hasRole('orang_tua') => 'portal.ortu.booklet.index',
            default => 'portal.siswa.booklet.index',
        };
    }

    private function showRouteName(): string
    {
        return match (true) {
            auth()->user()?->hasRole('guru') => 'portal.guru.booklet.show',
            auth()->user()?->hasRole('orang_tua') => 'portal.ortu.booklet.show',
            default => 'portal.siswa.booklet.show',
        };
    }
}
