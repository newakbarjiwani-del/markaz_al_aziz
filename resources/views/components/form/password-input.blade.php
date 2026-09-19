@props([
    'name',
    'id' => null,
    'label' => 'Password',
    'required' => false,
    'autocomplete' => 'new-password',
    'placeholder' => '',
    'hint' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    <label class="form-label" for="{{ $inputId }}">
        {{ $label }}
        @if($hint)
            <span class="text-muted text-xs font-normal">{{ $hint }}</span>
        @endif
    </label>
    <div class="relative">
        <input type="password"
               name="{{ $name }}"
               id="{{ $inputId }}"
               class="form-input pr-10"
               autocomplete="{{ $autocomplete }}"
               placeholder="{{ $placeholder }}"
               @if($required) required @endif>
        <button type="button"
                data-password-toggle="{{ $inputId }}"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-muted hover:text-primary-700 dark:hover:text-primary-300"
                aria-label="Tampilkan password"
                tabindex="-1">
            <x-icon name="eye" data-icon-show size="md" />
            <x-icon name="eye-off" data-icon-hide class="hidden" size="md" />
        </button>
    </div>
</div>
