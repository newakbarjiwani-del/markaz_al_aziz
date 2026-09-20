@extends('layouts.spmb', ['spmbNav' => 'home'])

@section('title', $title)

@section('content')
<section class="spmb-hero">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:py-20">
        <p class="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-primary-200">{{ config('app.nama_instansi') }}</p>
        <h1 class="spmb-hero__brand max-w-3xl">{{ config('app.name') }}</h1>
        <p class="mt-4 max-w-xl text-base text-primary-100/90 sm:text-lg">
            Penerimaan Murid Baru — informasi resmi, pengumuman, dan formulir pendaftaran online.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            @if($periode?->isOpen())
                <a href="{{ route('spmb.daftar') }}" class="btn-primary">Daftar Sekarang</a>
                <span class="inline-flex items-center rounded-md bg-white/10 px-3 py-2 text-sm text-primary-50">
                    Periode: {{ $periode->name }}
                </span>
            @else
                <a href="{{ route('spmb.pengumuman.index') }}" class="btn-secondary bg-white/10 text-white hover:bg-white/20">Lihat Pengumuman</a>
                <span class="inline-flex items-center rounded-md bg-amber-500/20 px-3 py-2 text-sm text-amber-50">
                    Pendaftaran sedang ditutup
                </span>
            @endif
        </div>
    </div>
</section>

<section class="spmb-section">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-6 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Pengumuman Terbaru</h2>
                <p class="text-sm text-slate-500">Jadwal, hasil, dan informasi penting SPMB.</p>
            </div>
            <a href="{{ route('spmb.pengumuman.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">Semua</a>
        </div>
        <div class="space-y-3">
            @forelse($pengumuman as $item)
                <a href="{{ route('spmb.pengumuman.show', $item) }}" class="block border-b border-slate-200 py-3 transition hover:border-primary-300 dark:border-slate-800">
                    <p class="font-medium text-slate-900 dark:text-white">{{ $item->title }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ optional($item->published_at)->translatedFormat('d M Y') ?? '—' }}</p>
                </a>
            @empty
                <p class="text-sm text-slate-500">Belum ada pengumuman.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="spmb-section bg-slate-50 dark:bg-slate-900/40">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-6 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Berita</h2>
                <p class="text-sm text-slate-500">Kegiatan dan kabar sekolah.</p>
            </div>
            <a href="{{ route('spmb.berita.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">Semua</a>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($berita as $item)
                <a href="{{ route('spmb.berita.show', $item) }}" class="group block">
                    @if($item->cover_path)
                        <img src="{{ asset('storage/'.$item->cover_path) }}" alt="" class="mb-3 aspect-[16/10] w-full object-cover">
                    @else
                        <div class="mb-3 aspect-[16/10] w-full bg-primary-100 dark:bg-primary-950"></div>
                    @endif
                    <h3 class="font-semibold text-slate-900 group-hover:text-primary-700 dark:text-white dark:group-hover:text-primary-300">{{ $item->title }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ optional($item->published_at)->translatedFormat('d M Y') }}</p>
                </a>
            @empty
                <p class="text-sm text-slate-500">Belum ada berita.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="spmb-section">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-6 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Galeri</h2>
                <p class="text-sm text-slate-500">Suasana kampus dan fasilitas.</p>
            </div>
            <a href="{{ route('spmb.galeri.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">Semua</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @forelse($galeri as $item)
                <figure class="overflow-hidden">
                    <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->title }}" class="aspect-square w-full object-cover">
                </figure>
            @empty
                <p class="col-span-full text-sm text-slate-500">Belum ada foto galeri.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
