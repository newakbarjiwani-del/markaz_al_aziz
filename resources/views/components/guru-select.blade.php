@props([
    'name' => 'guru_id',
    'id' => null,
    'label' => 'Guru',
    'required' => true,
    'value' => null,
    'selectedText' => null,
    'status' => 'aktif',
    'placeholder' => 'Cari nama atau NIP (min. 3 karakter)',
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
        :url="$lookupUrl ?? route('admin.guru.lookup')"
        :resolve-url="$lookupResolveUrl ?? url('admin/guru/lookup')"
        :placeholder="$placeholder"
        :required="$required"
        :value="$value"
        :selected-text="$selectedText"
        :extra-params="$extraParams"
    />
</div>
