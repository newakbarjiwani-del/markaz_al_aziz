@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Limit Global</h2>
            <p class="text-muted mt-1 text-sm">
                Satu pengaturan global per sekolah (disimpan di pengaturan cashless).
                Limit per siswa (jika diisi di bawah) dicek lebih dulu, lalu limit harian global ini.
            </p>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                Limit harian saat ini:
                <strong id="limit-global-current">Rp {{ number_format($globalDailyLimit ?? 0, 0, ',', '.') }}</strong>
                / siswa / hari
            </p>
        </div>
        <a href="{{ $cashlessSettingsUrl }}" class="btn-secondary btn-sm">
            Semua Setting Cashless
        </a>
    </div>

    <form data-fetch-form
          action="{{ route('admin.dompet-digital.limit-kontrol.global') }}"
          method="POST"
          class="mt-5 grid gap-4 md:grid-cols-3"
          data-reload-page>
        @csrf
        <div class="md:col-span-2">
            <label class="form-label" for="limit-global-daily">Limit harian (Rp)</label>
            <x-form.amount
                name="daily_transaction_limit"
                id="limit-global-daily"
                :min="0"
                :value="$globalDailyLimit"
                required
            />
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan Limit Harian
            </button>
        </div>
    </form>
</div>

<div class="card mb-4 p-4">
    <p class="text-muted text-sm">
        Kosongkan limit siswa untuk memakai limit global
        (Rp {{ number_format($globalDailyLimit ?? 0, 0, ',', '.') }}).
        Limit khusus siswa dicek lebih dulu saat belanja kantin.
        PIN cashless (untuk Tarik Saldo over-limit) dikelola di
        <a href="{{ route('admin.dompet-digital.rfid-kontrol') }}" class="font-medium text-primary-700 underline dark:text-primary-300">Kontrol RFID</a>.
    </p>
</div>

<x-admin.datatable-page
    title="Limit Harian Siswa"
    :ajax-url="route('admin.dompet-digital.limit-kontrol.data')"
    :columns="['NIS', 'Nama', 'Kelas', 'Limit Harian', 'Aksi']"
    :column-options="[
        3 => ['html' => true],
        4 => ['html' => true],
    ]"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        <div>
            <label class="form-label" for="filter-q">Cari Siswa</label>
            <input type="text" name="q" id="filter-q" class="form-input" placeholder="Nama atau NIS" value="{{ request('q') }}">
        </div>
        @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
        <div>
            <label class="form-label" for="filter-has-custom-limit">Jenis Limit</label>
            <select name="has_custom_limit" id="filter-has-custom-limit" class="form-input">
                <option value="">Semua</option>
                <option value="1">Limit khusus</option>
                <option value="0">Mengikuti global</option>
            </select>
        </div>
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>

<x-modal id="student-limit-modal" title="Ubah Limit Harian Siswa">
    <form id="student-limit-form"
          data-fetch-form
          method="POST"
          action="#"
          class="space-y-4"
          data-close-modal="student-limit-modal"
          data-reload-table>
        @csrf
        <div>
            <label class="form-label" for="student-limit-daily">Limit harian siswa (Rp)</label>
            <x-form.amount
                name="daily_transaction_limit"
                id="student-limit-daily"
                :min="0"
                placeholder="Kosongkan = global"
            />
            <p class="text-muted mt-1 text-xs">
                Kosongkan untuk memakai limit global
                (Rp {{ number_format($globalDailyLimit ?? 0, 0, ',', '.') }}).
            </p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-modal-close="student-limit-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endsection
