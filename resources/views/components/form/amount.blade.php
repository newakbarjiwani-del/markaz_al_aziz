@props([
    'name' => 'amount',
    'id' => null,
    'value' => '',
    'min' => 0,
    'max' => null,
    'required' => false,
    'placeholder' => '0',
    'class' => '',
])

@php
    $displayValue = $value !== '' && $value !== null
        ? number_format((int) $value, 0, ',', '.')
        : '';
@endphp

<input type="text"
       name="{{ $name }}"
       @if($id) id="{{ $id }}" @endif
       value="{{ $displayValue }}"
       class="form-input formattedNumber {{ $class }}"
       inputmode="numeric"
       min="{{ $min }}"
       @if($max !== null) max="{{ $max }}" @endif
       placeholder="{{ $placeholder }}"
       @required($required)>
