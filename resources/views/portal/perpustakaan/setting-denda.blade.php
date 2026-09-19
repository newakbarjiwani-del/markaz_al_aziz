@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-4 p-4">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Setting Nominal Denda</h2>
    <p class="mt-1 text-sm text-slate-500">Atur denda keterlambatan dan denda kondisi buku. Perubahan langsung dipakai saat pengembalian.</p>
</div>

<div class="card max-w-3xl p-6">
    @include('admin.perpustakaan.partials.setting-denda-form', [
        'formAction' => $formAction,
        'finePerDay' => $finePerDay,
        'kondisiFines' => $kondisiFines,
        'kondisiChoices' => $kondisiChoices,
    ])
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/library-fine-settings.js') }}?v=2"></script>
@endpush
