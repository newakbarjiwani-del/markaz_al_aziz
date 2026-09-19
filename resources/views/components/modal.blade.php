@props([
    'id',
    'title',
    'size' => 'md',
])

@php
    $panelMaxWidth = match ($size) {
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        '2xl' => 'max-w-6xl',
        'full' => 'max-w-7xl',
        default => 'max-w-lg',
    };
@endphp

<div id="{{ $id }}" {{ $attributes->merge(['class' => 'modal fixed inset-0 z-50 hidden overflow-y-auto']) }} role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="absolute inset-0 bg-black/50" data-modal-close="{{ $id }}"></div>
    <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center sm:p-6">
        <div class="modal-panel card relative my-4 w-full {{ $panelMaxWidth }} shadow-xl dark:shadow-black/40 sm:my-0">
            <div class="modal-panel__header">
                <h3 id="{{ $id }}-title" data-default-title="{{ $title }}" class="min-w-0 truncate text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                <button type="button" data-modal-close="{{ $id }}" class="modal-panel__close" aria-label="Tutup">
                    <x-icon name="x" size="md" />
                </button>
            </div>
            <div class="modal-panel__body">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
