@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-6 p-6">
    <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Form Pindah Kelas</h2>
    <form data-fetch-form action="{{ route('admin.manajemen-siswa.pindah-kelas.store') }}" method="POST" class="grid gap-4 md:grid-cols-3">
        @csrf
        <x-siswa-select id="pindah-kelas-siswa" />
        <div>
            <label class="form-label" for="pindah-kelas-tujuan">Kelas Tujuan</label>
            <select name="ke_kelas_id" id="pindah-kelas-tujuan" class="form-input" required>
                <option value="">Pilih kelas</option>
                @foreach($classes as $kelas)
                    <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="pindah-kelas-catatan">Catatan</label>
            <input type="text" name="notes" id="pindah-kelas-catatan" class="form-input" placeholder="Opsional">
        </div>
        <div class="md:col-span-3">
            <button type="submit" class="btn-primary">
                <x-icon name="arrows-exchange" size="sm" class="mr-1" /> Proses Pindah Kelas
            </button>
        </div>
    </form>
</div>

@include('admin.partials.datatable-page', [
    'tableTitle' => 'Riwayat Pindah Kelas',
    'ajaxUrl' => route('admin.manajemen-siswa.pindah-kelas.data'),
    'columns' => ['NIS', 'Nama', 'Dari Kelas', 'Ke Kelas', 'Status', 'Tanggal'],
])
@endsection
