@extends('layouts.app')

@section('title', $title)

@php
    $columnOptions = [
        ['orderable' => false, 'searchable' => false, 'exportable' => false],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

@section('content')
<div class="space-y-4">
    <div class="card p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Laporan Perpustakaan</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Rekap kunjungan perpustakaan siswa, guru, karyawan, dan pengunjung luar.</p>
            </div>
            <div class="rounded-xl border border-primary-200 bg-primary-50 px-3 py-2 text-sm text-primary-900 dark:border-primary-900/40 dark:bg-primary-950/30 dark:text-primary-100">
                <span class="font-semibold">Pengunjung Hari Ini:</span> {{ $todayCount }}
            </div>
        </div>
    </div>

    <div class="card card--datatable">
        <div class="filter-bar">
            <form id="filter-form" class="filter-form">
                <div>
                    <label class="form-label" for="filter-date-from">Dari Tanggal</label>
                    <input type="date" id="filter-date-from" name="date_from" class="form-input" value="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="form-label" for="filter-date-to">Sampai Tanggal</label>
                    <input type="date" id="filter-date-to" name="date_to" class="form-input" value="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="form-label" for="filter-visitor-type">Tipe</label>
                    <select id="filter-visitor-type" name="visitor_type" class="form-input">
                        <option value="">Semua</option>
                        <option value="siswa">Siswa</option>
                        <option value="guru">Guru</option>
                        <option value="karyawan">Karyawan</option>
                        <option value="non_siswa">Pengunjung Luar</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="filter-method">Metode</label>
                    <select id="filter-method" name="method" class="form-input">
                        <option value="">Semua</option>
                        <option value="manual">Manual</option>
                        <option value="face">Wajah</option>
                        <option value="rfid">RFID</option>
                    </select>
                </div>
                <x-filter-actions />
            </form>
        </div>

        <div class="card--datatable__body p-4 pt-0">
            <table id="main_table" class="datatable-main w-full display"
                   style="--table-min-width: 55rem"
                   data-ajax-url="{{ $ajaxUrl }}"
                   data-export-filename="Laporan Perpustakaan"
                   data-column-options="{{ json_encode($columnOptions) }}">
                <thead>
                    <tr>
                        @foreach(['No', 'Tanggal', 'Jam', 'Tipe', 'Nama', 'NIS/NIP', 'Asal/Kelas', 'Telepon', 'Metode', 'Foto'] as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div id="photo-preview-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="card max-w-md p-4">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-white">Foto Kunjungan</h3>
            <button type="button" id="photo-preview-close" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" aria-label="Tutup">
                <x-icon name="x" size="sm" />
            </button>
        </div>
        <img id="photo-preview-img" src="" alt="Foto kunjungan" class="w-full rounded-lg">
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    var button = event.target.closest('.visitor-photo-btn');
    if (!button) return;

    var id = button.getAttribute('data-visit-id');
    if (!id) return;

    var img = document.getElementById('photo-preview-img');
    var modal = document.getElementById('photo-preview-modal');
    if (!img || !modal) return;

    img.src = @json($photoUrlTemplate).replace('__ID__', id);
    modal.classList.remove('hidden');
});

document.getElementById('photo-preview-close')?.addEventListener('click', function () {
    document.getElementById('photo-preview-modal')?.classList.add('hidden');
});

document.getElementById('photo-preview-modal')?.addEventListener('click', function (event) {
    if (event.target?.id === 'photo-preview-modal') {
        event.currentTarget.classList.add('hidden');
    }
});
</script>
@endpush
