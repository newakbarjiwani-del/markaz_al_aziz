@props([
    'name' => 'tagihan_id',
    'id' => null,
    'label' => 'Tagihan',
    'required' => false,
    'value' => null,
    'selectedText' => null,
    'siswaId' => null,
    'unpaid' => true,
    'placeholder' => 'Cari siswa, jenis, atau periode (min. 3 karakter)',
])

@php
    $extraParams = array_filter([
        'siswa_id' => $siswaId,
        'unpaid' => $unpaid ? '1' : '0',
    ], fn ($value) => $value !== null && $value !== '');
    $inputId = $id ?: $name.'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
@endphp

<div {{ $attributes->merge(['class' => 'ajax-select-field']) }}>
    @if($label)
        <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
    @endif
    <x-ajax-select
        :name="$name"
        :id="$inputId"
        :url="route('admin.tagihan.lookup')"
        :resolve-url="url('admin/tagihan/lookup')"
        :placeholder="$placeholder"
        :required="$required"
        :value="$value"
        :selected-text="$selectedText"
        :extra-params="$extraParams"
    />
</div>
