@props([
    'name' => 'orang_tua_id',
    'id' => null,
    'label' => 'Orang Tua',
    'required' => true,
    'value' => null,
    'selectedText' => null,
    'status' => 'aktif',
    'placeholder' => 'Cari nama ayah/ibu atau telepon (min. 3 karakter)',
    'lookupUrl' => null,
    'lookupResolveUrl' => null,
])

@php
    $extraParams = $status !== null && $status !== '' ? ['status' => $status] : [];
    $inputId = $id ?: $name.'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
@endphp

<div {{ $attributes->merge(['class' => 'ajax-select-field']) }}>
    @if($label)
        <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
    @endif
    <x-ajax-select
        :name="$name"
        :id="$inputId"
        :url="$lookupUrl ?? route('admin.orang-tua.lookup')"
        :resolve-url="$lookupResolveUrl ?? url('admin/orang-tua/lookup')"
        :placeholder="$placeholder"
        :required="$required"
        :value="$value"
        :selected-text="$selectedText"
        :extra-params="$extraParams"
    />
</div>
