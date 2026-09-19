@props([
    'name' => 'peminjaman_id',
    'id' => null,
    'label' => 'Peminjaman Aktif',
    'required' => false,
    'value' => null,
    'selectedText' => null,
    'placeholder' => 'Cari siswa atau judul buku (min. 3 karakter)',
    'lookupUrl' => null,
    'lookupResolveUrl' => null,
])

@php
    $inputId = $id ?: $name.'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
@endphp

<div {{ $attributes->merge(['class' => 'ajax-select-field']) }}>
    @if($label)
        <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
    @endif
    <x-ajax-select
        :name="$name"
        :id="$inputId"
        :url="$lookupUrl ?? route('admin.peminjaman.lookup')"
        :resolve-url="$lookupResolveUrl ?? url('admin/peminjaman/lookup')"
        :placeholder="$placeholder"
        :required="$required"
        :value="$value"
        :selected-text="$selectedText"
    />
</div>
