@props([
    'selected' => null,
    'name' => 'gender',
    'id' => null,
    'label' => 'Gender',
])

<div>
    <label class="form-label" for="{{ $id ?? $name }}">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $id ?? $name }}" class="form-input">
        <option value="">Semua Gender</option>
        <option value="L" @selected((string) $selected === 'L')>Laki-laki</option>
        <option value="P" @selected((string) $selected === 'P')>Perempuan</option>
    </select>
</div>
