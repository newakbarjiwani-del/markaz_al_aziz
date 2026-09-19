@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Tracer Study"
    subtitle="Rekap respons tracer study alumni"
    :ajax-url="route('admin.alumni.tracer.data')"
    :columns="['Nama', 'NIS', 'Tahun', 'Status', 'Institusi', 'Dikirim', 'Sumber']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-tahun">Tahun</label>
                <input type="text" name="tahun_tracer" id="filter-tahun" class="form-input" placeholder="{{ now()->year }}">
            </div>
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="status_lulusan" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
