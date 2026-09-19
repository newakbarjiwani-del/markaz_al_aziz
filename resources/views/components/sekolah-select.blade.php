@props([
    'name' => 'sekolah_id',
    'id' => null,
    'label' => 'Sekolah',
    'required' => true,
    'value' => null,
    'placeholder' => 'Pilih sekolah',
    'schools' => [],
])

@php
    $selectId = $id ?? $name;
@endphp

<div {{ $attributes->merge(['class' => 'ajax-select-field']) }}>
    @if($label)
        <label class="form-label" for="{{ $selectId }}">{{ $label }}</label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $selectId }}"
        class="form-input"
        data-s2
        data-placeholder="{{ $placeholder }}"
        data-allow-clear="1"
        @if($required) required @endif
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($schools as $school)
            <option value="{{ $school->id }}" @selected((string) $value === (string) $school->id)>
                {{ $school->name }}
            </option>
        @endforeach
    </select>
</div>
