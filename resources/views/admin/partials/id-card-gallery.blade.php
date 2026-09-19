@props(['cards', 'cardType' => 'student', 'classes' => null])

@php
    $isStudent = $cardType === 'student';
    $emptyLabel = $isStudent ? 'Belum ada kartu pelajar aktif.' : 'Belum ada kartu guru aktif.';
    $logoUrl = asset('logo.png');
@endphp

@if($isStudent && $classes)
<div class="card card--datatable mb-4">
    <div class="card-divider p-4">
        <h2 class="card-title">Kartu Pelajar</h2>
    </div>
    <div class="filter-bar">
        <form method="GET" id="filter-form" data-filter-mode="navigate" class="filter-form">
            @include('admin.partials.filters.student-search', [
                'value' => request('q'),
                'colClass' => 'min-w-[14rem] flex-1',
                'id' => 'filter-nama',
            ])
            @include('admin.partials.filters.class-select', [
                'classes' => $classes,
                'selected' => request('kelas_id'),
                'colClass' => 'min-w-[12rem]',
                'id' => 'filter-kelas',
            ])
            <x-filter-actions />
        </form>
    </div>
    <div class="card--datatable__body p-4 pt-0">
@endif

<div class="mb-4 flex flex-wrap items-center justify-between gap-4">
    <p class="text-sm text-slate-500">
        Menampilkan {{ $cards->firstItem() ?? 0 }}–{{ $cards->lastItem() ?? 0 }} dari {{ $cards->total() }} kartu aktif
    </p>
</div>

@if($cards->isEmpty())
    <div class="card p-8 text-center text-slate-500">{{ $emptyLabel }}</div>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @foreach($cards as $card)
            @php
                $person = $isStudent ? $card->siswa : $card->guru;
            @endphp
            @if(!$person) @continue @endif

            @php
                $title = $person->name;
                $code = $isStudent ? ($person->nis ?? '-') : ($person->nip ?? '-');
                $subtitle = $isStudent
                    ? ($person->kelas?->name ?? '-')
                    : ($person->jabatan ?? '-');
                $detailId = 'id-card-detail-'.$cardType.'-'.$card->id;
                $studentPhotoUrl = $isStudent ? ($person->profil?->photoUrl()) : null;
            @endphp

            <button type="button"
                    class="card id-card-tile group w-full overflow-hidden text-left transition-shadow hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                    data-id-card-open="{{ $detailId }}"
                    data-id-card-title="{{ $title }} ({{ $code }})">
                <div class="id-card-preview border-b border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/50">
                    <div class="id-card-preview__scale">
                        @if($isStudent)
                            <x-id-card.student-front :siswa="$person" :photo-url="$studentPhotoUrl" :logo-url="$logoUrl" />
                        @else
                            <x-id-card.teacher-front :guru="$person" :logo-url="$logoUrl" />
                        @endif
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900 group-hover:text-primary-700 dark:text-white dark:group-hover:text-primary-300">{{ $title }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $code }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $subtitle }}</p>
                        </div>
                        <span class="badge badge-success shrink-0">{{ ucfirst($card->status) }}</span>
                    </div>
                    <p class="mt-3 flex items-center gap-1 text-xs font-medium text-primary-600 dark:text-primary-400">
                        <x-icon name="eye" size="sm" /> Lihat & cetak
                    </p>
                </div>
            </button>

            <template id="{{ $detailId }}">
                <div class="id-card-modal-faces">
                    <div class="id-card-modal-face">
                        <p class="id-card-set__label">Depan</p>
                        @if($isStudent)
                            <x-id-card.student-front :siswa="$person" :photo-url="$studentPhotoUrl" :logo-url="$logoUrl" />
                        @else
                            <x-id-card.teacher-front :guru="$person" :logo-url="$logoUrl" />
                        @endif
                    </div>
                    <div class="id-card-modal-face">
                        <p class="id-card-set__label">Belakang</p>
                        @if($isStudent)
                            <x-id-card.student-back :nis="$person->nis" :logo-url="$logoUrl" />
                        @else
                            <x-id-card.teacher-back :nip="$person->nip" :logo-url="$logoUrl" />
                        @endif
                    </div>
                </div>
            </template>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $cards->links('vendor.pagination.default') }}
    </div>
@endif

@if($isStudent && $classes)
    </div>
</div>
@endif

{{-- Detail modal --}}
<div id="id-card-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="id-card-modal-title">
    <div class="absolute inset-0 bg-black/50" data-id-card-modal-close></div>
    <div class="relative flex min-h-full items-start justify-center p-3 pt-6 sm:items-center sm:p-4">
        <div class="card relative flex max-h-[calc(100dvh-1.5rem)] w-full max-w-5xl flex-col overflow-hidden shadow-xl sm:max-h-[calc(100dvh-2rem)]">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 dark:border-slate-800 sm:px-6">
                <h3 id="id-card-modal-title" class="min-w-0 truncate text-lg font-semibold text-slate-900 dark:text-white">Detail Kartu</h3>
                <button type="button"
                        data-id-card-modal-close
                        class="shrink-0 rounded-lg p-1 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                        aria-label="Tutup">
                    <x-icon name="x" size="md" />
                </button>
            </div>
            <div id="id-card-modal-content" class="id-card-modal-content min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6"></div>
            <div class="flex shrink-0 justify-end border-t border-slate-200 px-4 py-3 dark:border-slate-800 sm:px-6">
                <button type="button" class="btn-primary btn-sm w-full sm:w-auto" data-id-card-print>
                    <x-icon name="printer" size="sm" class="mr-1" /> Cetak Kartu Ini
                </button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.__idCardPrintAssets = {
                css: @json(asset('css/id-card.css') . '?v=19'),
                icons: @json('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css'),
                font: @json('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap'),
                logo: @json($logoUrl),
            };
        </script>
        <script src="{{ asset('js/id-card.js') }}?v=5"></script>
    @endpush
@endonce
