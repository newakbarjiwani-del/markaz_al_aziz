@props([
    'value' => null,
    'name' => 'q',
    'id' => null,
    'label' => 'Nama / NIS',
    'placeholder' => 'Cari nama atau NIS...',
    'colClass' => 'min-w-[14rem]',
])

<div class="{{ $colClass }}">
    <label class="form-label" for="{{ $id ?? $name }}">{{ $label }}</label>
    <input type="search"
           name="{{ $name }}"
           id="{{ $id ?? $name }}"
           value="{{ $value }}"
           class="form-input"
           placeholder="{{ $placeholder }}"
           autocomplete="off">
</div>
