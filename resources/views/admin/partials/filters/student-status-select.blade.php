@props([
    'selected' => null,
    'name' => 'status',
    'id' => null,
    'label' => 'Status Siswa',
])

@php
    use App\Support\SiswaStatus;
    $selected = $selected === null || $selected === '' ? null : (string) $selected;
@endphp

<div>
    <label class="form-label" for="{{ $id ?? $name }}">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $id ?? $name }}" class="form-input">
        <option value="">Semua Status</option>
        @foreach(SiswaStatus::labels() as $value => $optionLabel)
            <option value="{{ $value }}" @selected($selected === (string) $value)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
