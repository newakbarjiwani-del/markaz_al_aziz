@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/id-card.css') }}?v=19">
@endpush

@section('content')
@if(isset($children) && $children->count() > 1)
<div class="card card--datatable mb-4">
    <div class="card-divider p-4">
        <h2 class="card-title">Kartu Pelajar</h2>
    </div>
    <div class="filter-bar">
        <form method="GET" id="filter-form" data-filter-mode="navigate" class="filter-form">
            <div class="min-w-[16rem] flex-1">
                <label class="form-label">Pilih Anak</label>
                <select name="siswa_id" class="form-input">
                    <option value="">Semua anak</option>
                    @foreach($children as $child)
                        <option value="{{ $child->id }}" @selected((int) request('siswa_id') === $child->id)>
                            {{ $child->nis }} — {{ $child->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </div>
    <div class="card--datatable__body p-4 pt-0">
@endif

@include('admin.partials.id-card-gallery', [
    'cards' => $cards,
    'cardType' => $cardType,
])

@if(isset($children) && $children->count() > 1)
    </div>
</div>
@endif
@endsection
