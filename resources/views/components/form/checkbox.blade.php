@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'hiddenFallback' => true,
    'checkboxClass' => '',
    'dataDay' => null,
])

@php
    $showLabel = $label !== false && ($label !== null || ! $slot->isEmpty());
@endphp

<label {{ $attributes->class(['form-check']) }}>
    @if($name && $hiddenFallback)
        <input type="hidden" name="{{ $name }}" value="0">
    @endif
    <input type="checkbox"
           @if($name) name="{{ $name }}" @endif
           @if($id) id="{{ $id }}" @endif
           value="{{ $value }}"
           @class(['form-checkbox', $checkboxClass])
           @checked($checked)
           @disabled($disabled)
           @if($dataDay !== null) data-day="{{ $dataDay }}" @endif
           {{ $attributes->only(['title', 'aria-label']) }}>
    @if($showLabel)
        <span>{{ $label ?? $slot }}</span>
    @endif
</label>
