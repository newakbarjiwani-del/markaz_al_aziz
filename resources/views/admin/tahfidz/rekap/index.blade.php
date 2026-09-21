@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 card p-5">
    <h2 class="mb-3 text-lg font-semibold">Buat rekap minggu</h2>
    <form method="POST" action="{{ route('admin.tahfidz.rekap.store') }}" class="grid gap-4 sm:grid-cols-4">
        @csrf
        <div>
            <label class="form-label" for="rekap-program_id">Program</label>
            <select name="program_id" id="rekap-program_id" class="form-input" required>
                <option value="">Pilih</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="rekap-starts_on">Mulai</label>
            <input type="date" name="starts_on" id="rekap-starts_on" class="form-input" required>
        </div>
        <div>
            <label class="form-label" for="rekap-ends_on">Selesai</label>
            <input type="date" name="ends_on" id="rekap-ends_on" class="form-input" required>
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary">Buat / buka</button>
        </div>
    </form>
</div>

<x-admin.datatable-page
    title="Rekap Mingguan"
    subtitle="Pencapaian tatsbit, murojaah, dan kehadiran per minggu"
    :ajax-url="route('admin.tahfidz.rekap.data')"
    :columns="['Program', 'Periode', 'Status', 'Santri', 'Aksi']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-program_id">Program</label>
                <select name="program_id" id="filter-program_id" class="form-input">
                    <option value="">Semua</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->label() }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
