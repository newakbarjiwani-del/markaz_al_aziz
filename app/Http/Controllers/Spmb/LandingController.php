<?php

namespace App\Http\Controllers\Spmb;

use App\Http\Controllers\Controller;
use App\Models\SpmbBerita;
use App\Models\SpmbGaleriItem;
use App\Models\SpmbPengumuman;
use App\Models\SpmbPeriode;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function home(): View
    {
        $periode = SpmbPeriode::currentOpen();

        return view('spmb.home', [
            'title' => 'SPMB — Penerimaan Murid Baru',
            'periode' => $periode,
            'pengumuman' => SpmbPengumuman::query()->published()->orderByDesc('published_at')->limit(5)->get(),
            'berita' => SpmbBerita::query()->published()->orderByDesc('published_at')->limit(3)->get(),
            'galeri' => SpmbGaleriItem::query()->published()->orderBy('sort_order')->orderByDesc('id')->limit(8)->get(),
        ]);
    }

    public function pengumumanIndex(): View
    {
        return view('spmb.pengumuman.index', [
            'title' => 'Pengumuman SPMB',
            'items' => SpmbPengumuman::query()->published()->orderByDesc('published_at')->paginate(10),
        ]);
    }

    public function pengumumanShow(SpmbPengumuman $pengumuman): View
    {
        abort_unless($this->isPublishedPengumuman($pengumuman), 404);

        return view('spmb.pengumuman.show', [
            'title' => $pengumuman->title,
            'pengumuman' => $pengumuman,
        ]);
    }

    public function beritaIndex(): View
    {
        return view('spmb.berita.index', [
            'title' => 'Berita SPMB',
            'items' => SpmbBerita::query()->published()->orderByDesc('published_at')->paginate(9),
        ]);
    }

    public function beritaShow(SpmbBerita $berita): View
    {
        abort_unless($this->isPublishedBerita($berita), 404);

        return view('spmb.berita.show', [
            'title' => $berita->title,
            'berita' => $berita,
        ]);
    }

    public function galeriIndex(): View
    {
        return view('spmb.galeri.index', [
            'title' => 'Galeri Sekolah',
            'items' => SpmbGaleriItem::query()->published()->orderBy('sort_order')->orderByDesc('id')->paginate(24),
        ]);
    }

    private function isPublishedPengumuman(SpmbPengumuman $pengumuman): bool
    {
        if (! $pengumuman->is_published) {
            return false;
        }

        return $pengumuman->published_at === null || $pengumuman->published_at->lte(now());
    }

    private function isPublishedBerita(SpmbBerita $berita): bool
    {
        if (! $berita->is_published) {
            return false;
        }

        return $berita->published_at === null || $berita->published_at->lte(now());
    }
}
