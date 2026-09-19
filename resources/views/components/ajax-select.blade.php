@props([
    'name',
    'id' => null,
    'url',
    'resolveUrl' => null,
    'placeholder' => 'Ketik minimal 3 karakter...',
    'required' => false,
    'value' => null,
    'selectedText' => null,
    'minLength' => 3,
    'allowClear' => true,
    'extraParams' => [],
    'class' => 'ajax-select-native',
])

@php
    $selectId = $id ?: $name.'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
    $resolveUrl = $resolveUrl ?? rtrim($url, '/');
@endphp

<select
    name="{{ $name }}"
    id="{{ $selectId }}"
    {{ $attributes->merge(['class' => $class]) }}
    data-ajax-select
    data-url="{{ $url }}"
    data-resolve-url="{{ $resolveUrl }}"
    data-placeholder="{{ $placeholder }}"
    data-min-length="{{ $minLength }}"
    data-allow-clear="{{ $allowClear ? '1' : '0' }}"
    data-extra-params="{{ json_encode((object) $extraParams) }}"
    @if($required) required @endif
>
    @if($value && $selectedText)
        <option value="{{ $value }}" selected>{{ $selectedText }}</option>
    @endif
</select>
