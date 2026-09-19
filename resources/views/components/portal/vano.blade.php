@props([
    'value' => null,
    'label' => 'No. Virtual Account',
    'empty' => '-',
    'hint' => null,
    'compact' => false,
])

@php
    $vano = filled($value) ? preg_replace('/\D/', '', (string) $value) : null;
    $vano = ($vano !== null && $vano !== '') ? $vano : null;
    $display = $vano !== null
        ? trim(chunk_split($vano, 4, ' '))
        : null;
@endphp

@if($vano === null)
    <span {{ $attributes->merge(['class' => 'text-muted']) }}>{{ $empty }}</span>
@else
    <div {{ $attributes->class(['portal-vano', 'portal-vano--compact' => $compact]) }}>
        @if($label !== '')
            <span class="portal-vano__label">{{ $label }}</span>
        @endif
        <div class="portal-vano__row">
            <span class="portal-vano__value font-mono" title="{{ $vano }}">{{ $display }}</span>
            <button type="button"
                    class="btn-action btn-action--icon portal-vano__copy"
                    data-copy-text="{{ $vano }}"
                    data-copy-success="No. Virtual Account berhasil disalin."
                    title="Salin No. Virtual Account"
                    aria-label="Salin No. Virtual Account {{ $vano }}">
                <i class="ti ti-clipboard-copy" aria-hidden="true"></i>
                <span class="btn-action-label portal-vano__copy-label">Salin</span>
            </button>
        </div>
        @if(filled($hint))
            <p class="portal-vano__hint">{{ $hint }}</p>
        @endif
    </div>
@endif
