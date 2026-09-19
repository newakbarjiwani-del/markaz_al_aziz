@props([
    'selected' => null,
    'name' => 'has_foto_wajah',
    'id' => null,
    'label' => 'Rekam Wajah (Absensi)',
])

<div>
    <label class="form-label" for="{{ $id ?? $name }}">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $id ?? $name }}" class="form-input">
        <option value="">Semua</option>
        <option value="1" @selected((string) $selected === '1')>Sudah direkam</option>
        <option value="0" @selected((string) $selected === '0')>Belum direkam</option>
    </select>
</div>
