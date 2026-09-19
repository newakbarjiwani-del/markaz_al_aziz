@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Laporan Nilai"
    subtitle="Agregasi rata-rata nilai per siswa dan mapel"
    :ajax-url="route('admin.akademik.laporan-nilai.data')"
    :columns="['Siswa', 'Kelas', 'Mapel', 'Tahun', 'Semester', 'Rata-rata', 'Entri', 'Min', 'Max']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-tahun">Tahun</label>
                <select name="tahun_akademik_id" id="filter-tahun" class="form-input" data-s2>
                    <option value="">Semua</option>
                    @foreach($tahunAkademik as $ta)
                        <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-semester">Semester</label>
                <select name="semester" id="filter-semester" class="form-input">
                    <option value="">Semua</option>
                    @foreach($semesters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-kelas">Kelas</label>
                <select name="kelas_id" id="filter-kelas" class="form-input" data-s2>
                    <option value="">Semua</option>
                    @foreach($classes as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-mapel">Mapel</label>
                <select name="mata_pelajaran_id" id="filter-mapel" class="form-input" data-s2>
                    <option value="">Semua</option>
                    @foreach($mapelOptions as $mapel)
                        <option value="{{ $mapel->id }}">{{ $mapel->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
