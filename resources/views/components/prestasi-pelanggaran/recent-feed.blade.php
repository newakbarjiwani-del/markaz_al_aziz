@props([
    'title' => '',
    'href' => null,
    'tone' => 'success', // success | danger
    'empty' => 'Belum ada data.',
    'rows' => [],
])

@php
    $isSuccess = $tone === 'success';
@endphp

<section class="pp-feed card">
    <div class="pp-feed__head">
        <div>
            <h3 class="pp-feed__title">{{ $title }}</h3>
            <p class="pp-feed__subtitle">5 catatan terakhir</p>
        </div>
        @if($href)
            <a href="{{ $href }}" class="btn-secondary text-xs sm:text-sm">Lihat</a>
        @endif
    </div>

    @if($rows->isEmpty())
        <p class="pp-feed__empty">{{ $empty }}</p>
    @else
        <ul class="pp-feed__list">
            @foreach($rows as $record)
                @php
                    $entity = $record->siswa ?? $record->guru;
                    $name = $entity?->name ?? '-';
                    $subtitle = $record->siswa
                        ? ($record->siswa?->kelas?->name ?? '')
                        : ($record->guru?->nip ?? '');
                @endphp
                <li class="pp-feed__row">
                    <div class="pp-feed__main">
                        <p class="pp-feed__name">{{ $name }}</p>
                        <p class="pp-feed__meta">
                            <span class="pp-feed__judul">{{ $record->judul }}</span>
                            @if($subtitle !== '')
                                <span class="pp-feed__dot" aria-hidden="true">·</span>
                                <span>{{ $subtitle }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="pp-feed__aside">
                        <span @class([
                            'pp-feed__point',
                            'pp-feed__point--success' => $isSuccess,
                            'pp-feed__point--danger' => ! $isSuccess,
                        ])>{{ $record->point }}</span>
                        <span class="pp-feed__date">{{ \App\Support\DisplayDate::date($record->tanggal) }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
