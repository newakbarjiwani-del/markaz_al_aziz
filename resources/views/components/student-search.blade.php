@props([
    'inputId' => 'student-search-input',
    'hiddenId' => 'selected-siswa-id',
    'resultsId' => 'student-search-results',
    'summaryId' => 'student-search-summary',
    'label' => 'Cari Siswa',
    'placeholder' => 'Ketik nama atau NIS...',
    'lookupUrl' => null,
])

@php
    $lookupUrl = $lookupUrl ?? route('admin.siswa.lookup');
@endphp

<div {{ $attributes->merge(['class' => 'student-search relative', 'data-student-search' => true]) }}
     data-lookup-url="{{ $lookupUrl }}">
    <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
    <input type="text"
           id="{{ $inputId }}"
           class="form-input student-search-input"
           placeholder="{{ $placeholder }}"
           autocomplete="off"
           data-student-search-input>
    <input type="hidden" id="{{ $hiddenId }}" data-student-search-id>
    <div id="{{ $resultsId }}"
         class="student-search-results absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
         data-student-search-results></div>
    <div id="{{ $summaryId }}"
         class="student-search-summary mt-3 hidden rounded-lg border border-primary-200 bg-primary-50 p-3 text-sm dark:border-primary-800 dark:bg-primary-950"
         data-student-search-summary></div>
</div>
