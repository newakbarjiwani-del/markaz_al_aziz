@props([
    'name' => 'buku_id',
    'id' => null,
    'label' => 'Buku',
    'required' => true,
    'value' => null,
    'selectedText' => null,
    'placeholder' => 'Cari judul, penulis, atau ISBN (min. 3 karakter)',
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
        :url="$lookupUrl ?? route('admin.buku.lookup')"
        :resolve-url="$lookupResolveUrl ?? url('admin/buku/lookup')"
        :placeholder="$placeholder"
        :required="$required"
        :value="$value"
        :selected-text="$selectedText"
    />
</div>
