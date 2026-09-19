@props([
    'label' => 'Menu',
    'icon' => null,
    'triggerIcon' => 'chevron-down',
    'variant' => 'secondary',
    'size' => '',
    'align' => 'end',
    'disabled' => false,
    'menuLabel' => null,
    'split' => false,
])

@php
    $triggerClass = match ($variant) {
        'primary' => 'btn-primary',
        'accent' => 'btn-accent',
        'danger' => 'btn-danger',
        default => 'btn-secondary',
    };

    $sizeClass = match ($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };

    $alignClass = $align === 'start' ? 'dropdown-button--align-start' : 'dropdown-button--align-end';
@endphp

<div {{ $attributes->class(['dropdown-button', $alignClass]) }} data-dropdown-button>
    @if($split)
        <div class="dropdown-button__split">
            <button type="button"
                    class="{{ trim("$triggerClass $sizeClass dropdown-button__split-main") }}"
                    @disabled($disabled)>
                <span class="dropdown-button__spinner" aria-hidden="true"></span>
                @if($icon)
                    <x-icon :name="$icon" size="sm" class="dropdown-button__icon" />
                @endif
                <span data-dropdown-button-label>{{ $label }}</span>
            </button>
            <button type="button"
                    class="{{ trim("$triggerClass $sizeClass dropdown-button__split-toggle") }}"
                    data-dropdown-button-toggle
                    aria-expanded="false"
                    aria-haspopup="menu"
                    aria-label="{{ $menuLabel ?? $label }}"
                    @disabled($disabled)>
                <x-icon :name="$triggerIcon" class="dropdown-button__chevron" size="sm" aria-hidden="true" />
            </button>
        </div>
    @else
        <button type="button"
                class="{{ trim("$triggerClass $sizeClass dropdown-button__trigger") }}"
                data-dropdown-button-toggle
                aria-expanded="false"
                aria-haspopup="menu"
                @disabled($disabled)>
            <span class="dropdown-button__spinner" aria-hidden="true"></span>
            @if($icon)
                <x-icon :name="$icon" size="sm" class="dropdown-button__icon" />
            @endif
            <span data-dropdown-button-label>{{ $label }}</span>
            <x-icon :name="$triggerIcon" class="dropdown-button__chevron" size="sm" aria-hidden="true" />
        </button>
    @endif

    <div class="dropdown-button__panel"
         data-dropdown-button-panel
         role="menu"
         @if($menuLabel) aria-label="{{ $menuLabel }}" @endif>
        {{ $slot }}
    </div>
</div>
