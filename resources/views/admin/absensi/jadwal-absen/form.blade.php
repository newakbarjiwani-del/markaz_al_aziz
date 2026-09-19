@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $selectedSekolah = old('sekolah_id', $jadwal?->sekolah_id ?? ($schools->count() > 1 ? 'all' : $schools->first()?->id));
    if ($selectedSekolah === null) {
        $selectedSekolah = 'all';
    }
    $selectedAssignment = old('assignment_type', $jadwal?->assignment_type ?? 'kelas');
    if ($selectedAssignment === 'all_schools') {
        $selectedAssignment = 'sekolah';
    }
    $selectedKelasIds = $jadwal?->kelas->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [];
    $daysPayload = [];
    foreach ($weekdays as $dayNum => $dayLabel) {
        $hari = $jadwal?->hari->firstWhere('day_of_week', $dayNum);
        $daysPayload[$dayNum] = [
            'active' => (bool) ($hari?->is_active),
            'slots' => $hari
                ? $hari->slots->map(fn ($slot) => [
                    'pelajaran_id' => (string) $slot->pelajaran_id,
                    'guru_id' => (string) $slot->guru_id,
                    'time_start' => \Illuminate\Support\Str::of($slot->time_start)->substr(0, 5),
                    'time_end' => \Illuminate\Support\Str::of($slot->time_end)->substr(0, 5),
                    'tolerance_minutes' => (string) $slot->tolerance_minutes,
                ])->values()->all()
                : [],
        ];
    }
@endphp

<div class="mb-4">
    <a href="{{ route('admin.absensi.jadwal-absen.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400">
        <x-icon name="arrow-left" size="sm" /> Kembali ke daftar jadwal
    </a>
</div>

<form id="jadwal-absen-form"
      class="jadwal-absen-form space-y-6 pb-10 mb-6"
      action="{{ $formAction }}"
      method="POST"
      data-jadwal-absen-form
      data-preview-url="{{ route('admin.absensi.jadwal-absen.preview-students') }}"
      data-redirect-url="{{ route('admin.absensi.jadwal-absen.index') }}">
    @csrf
    @if($formMethod !== 'POST')
        <input type="hidden" name="_method" value="{{ $formMethod }}">
    @endif

    <div class="card p-5">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Informasi Jadwal</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="jadwal-name" class="form-label">Nama Jadwal Absen</label>
                <input type="text" name="name" id="jadwal-name" class="form-input" value="{{ old('name', $jadwal?->name) }}" placeholder="Contoh: Jadwal Absen Kelas MA" required>
            </div>
            <div>
                <label for="jadwal-sekolah-id" class="form-label">Sekolah</label>
                <select name="sekolah_id" id="jadwal-sekolah-id" class="form-input" required data-s2>
                    @if($schools->count() > 1)
                        <option value="all" @selected((string) $selectedSekolah === 'all')>
                            Semua Sekolah
                        </option>
                    @endif
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" @selected((string) $selectedSekolah === (string) $school->id)>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="jadwal-status" class="form-label">Status</label>
                <select name="is_active" id="jadwal-status" class="form-input">
                    <option value="1" @selected(old('is_active', $jadwal?->is_active ?? true))>Aktif</option>
                    <option value="0" @selected(! old('is_active', $jadwal?->is_active ?? true))>Nonaktif</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="jadwal-notes" class="form-label">Catatan</label>
                <textarea name="notes" id="jadwal-notes" class="form-input" rows="2" placeholder="Opsional">{{ old('notes', $jadwal?->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="card p-5">
        <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Cakupan Siswa</h2>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Tentukan siswa yang mengikuti jadwal absen ini berdasarkan sekolah atau kelas.</p>

        <div class="mb-4 flex flex-wrap gap-4">
            <label class="form-check">
                <input type="radio" name="assignment_type" value="sekolah" class="form-radio"
                       @checked($selectedAssignment === 'sekolah')>
                <span>Semua siswa di sekolah terpilih</span>
            </label>
            <label class="form-check">
                <input type="radio" name="assignment_type" value="kelas" class="form-radio"
                       @checked($selectedAssignment === 'kelas')>
                <span>Per kelas tertentu</span>
            </label>
        </div>

        <div id="jadwal-kelas-panel" class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Pilih kelas</p>
                <label class="form-check text-sm">
                    <input type="checkbox" id="jadwal-select-all-kelas" class="form-checkbox">
                    <span>Pilih semua kelas</span>
                </label>
            </div>
            <div id="jadwal-kelas-list" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($classes as $class)
                    <div class="jadwal-kelas-item" data-sekolah-id="{{ $class->sekolah_id }}">
                        <x-form.checkbox
                            name="kelas_ids[]"
                            :value="$class->id"
                            :checked="in_array((string) $class->id, $selectedKelasIds, true)"
                            :hidden-fallback="false">
                            {{ $class->name }} <span class="text-xs text-slate-500">({{ $class->sekolah?->name }})</span>
                        </x-form.checkbox>
                    </div>
                @endforeach
            </div>
            <p id="jadwal-kelas-empty" class="mt-2 hidden text-sm text-slate-500 dark:text-slate-400">
                Tidak ada kelas untuk sekolah yang dipilih.
            </p>
            <p id="jadwal-all-schools-hint" class="mt-2 hidden text-xs text-slate-500 dark:text-slate-400">
                Mode «Semua Sekolah»: daftar kelas dari seluruh sekolah ditampilkan. Centang kelas yang diikuti jadwal ini.
            </p>
        </div>

        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
            Estimasi siswa terdaftar: <strong id="jadwal-student-count">-</strong>
        </p>
    </div>

    <div class="card p-5">
        <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Hari & Pelajaran</h2>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Centang hari aktif, lalu atur pelajaran, guru pengampu, jam mulai/selesai, dan toleransi keterlambatan.</p>

        <div class="space-y-4" id="jadwal-days-root">
            @foreach($weekdays as $dayNum => $dayLabel)
                <div class="jadwal-day-card rounded-xl border border-slate-200 dark:border-slate-700" data-day="{{ $dayNum }}">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                        <x-form.checkbox
                            name="days[{{ $dayNum }}][active]"
                            :label="$dayLabel"
                            :hidden-fallback="false"
                            checkbox-class="jadwal-day-toggle"
                            :data-day="$dayNum"
                            class="font-medium text-slate-900 dark:text-white" />
                        <button type="button" class="btn-secondary btn-sm jadwal-add-slot hidden" data-day="{{ $dayNum }}">
                            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Pelajaran
                        </button>
                    </div>
                    <div class="jadwal-day-body hidden p-4">
                        <div class="mb-2 hidden grid-cols-12 gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:grid">
                            <span class="col-span-3">Pelajaran</span>
                            <span class="col-span-3">Guru</span>
                            <span class="col-span-2">Mulai</span>
                            <span class="col-span-2">Selesai</span>
                            <span class="col-span-1">Toleransi</span>
                            <span class="col-span-1"></span>
                        </div>
                        <div class="jadwal-slots-list space-y-2" data-day="{{ $dayNum }}"></div>
                        <p class="jadwal-day-empty mt-2 text-sm text-slate-500">Belum ada pelajaran untuk hari ini.</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap justify-end gap-2 pt-2">
        <a href="{{ route('admin.absensi.jadwal-absen.index') }}" class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary">
            <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan Jadwal
        </button>
    </div>
</form>

<template id="jadwal-slot-template">
    <div class="jadwal-slot-row grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/40 sm:grid-cols-12 sm:items-end">
        <div class="sm:col-span-3">
            <label class="form-label sm:sr-only">Pelajaran</label>
            <select class="form-input jadwal-slot-pelajaran" data-name-template="days[__DAY__][slots][__INDEX__][pelajaran_id]" required>
                <option value="">Pilih pelajaran</option>
            </select>
        </div>
        <div class="sm:col-span-3">
            <label class="form-label sm:sr-only">Guru</label>
            <select class="form-input jadwal-slot-guru" data-name-template="days[__DAY__][slots][__INDEX__][guru_id]" required>
                <option value="">Pilih guru</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="form-label sm:sr-only">Mulai</label>
            <input type="time" class="form-input jadwal-slot-start" data-name-template="days[__DAY__][slots][__INDEX__][time_start]" required>
        </div>
        <div class="sm:col-span-2">
            <label class="form-label sm:sr-only">Selesai</label>
            <input type="time" class="form-input jadwal-slot-end" data-name-template="days[__DAY__][slots][__INDEX__][time_end]" required>
        </div>
        <div class="sm:col-span-1">
            <label class="form-label sm:sr-only">Menit</label>
            <input type="number" min="0" max="120" value="15" class="form-input jadwal-slot-tolerance" data-name-template="days[__DAY__][slots][__INDEX__][tolerance_minutes]">
        </div>
        <div class="sm:col-span-1">
            <button type="button" class="btn-danger btn-sm w-full jadwal-remove-slot">Hapus</button>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
window.jadwalAbsenFormConfig = {
    csrfToken: @json(csrf_token()),
    pelajaranList: @json($pelajaranList->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sekolah_id' => $p->sekolah_id])),
    guruList: @json($guruList->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'sekolah_id' => $g->sekolah_id])),
    initialDays: @json($daysPayload),
};
</script>
<script src="{{ asset('js/jadwal-absen-form.js') }}?v=9"></script>
@endpush
