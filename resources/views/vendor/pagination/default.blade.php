@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">
        <p class="text-sm text-slate-500">
            Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
        </p>
        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn-secondary btn-sm pointer-events-none opacity-50">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn-secondary btn-sm" rel="prev">Sebelumnya</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn-primary btn-sm pointer-events-none">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn-secondary btn-sm">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn-secondary btn-sm" rel="next">Berikutnya</a>
            @else
                <span class="btn-secondary btn-sm pointer-events-none opacity-50">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif
