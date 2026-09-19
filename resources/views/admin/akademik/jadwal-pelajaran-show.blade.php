@extends('layouts.app')

@section('title', $title)

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.akademik.jadwal-pelajaran.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Kembali ke daftar</a>
</div>

<div class="card mb-6 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $jadwal->name ?: ($jadwal->kelas?->name ?? 'Jadwal') }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $jadwal->kelas?->name ?? '-' }}
                · {{ $jadwal->tahunAkademik?->name ?? '-' }}
                · {{ $jadwal->sekolah?->name ?? 'Semua sekolah' }}
            </p>
        </div>
        @can('akademik.create')
            <button type="button" class="btn-primary" data-open-modal="akademik-slot-modal">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Slot
            </button>
        @endcan
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-scroll">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Hari</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Jam</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Mapel</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Guru</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Ruang</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jadwal->slots as $slot)
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="px-4 py-3">{{ $slot->dayLabel() }}</td>
                        <td class="px-4 py-3 font-mono">
                            {{ \Illuminate\Support\Str::of($slot->time_start)->substr(0, 5) }}
                            –
                            {{ \Illuminate\Support\Str::of($slot->time_end)->substr(0, 5) }}
                        </td>
                        <td class="px-4 py-3">{{ $slot->mataPelajaran?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $slot->guru?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $slot->ruang ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('akademik.delete')
                                <button type="button" class="btn-action btn-action-delete"
                                        data-fetch-delete="{{ route('admin.akademik.jadwal-pelajaran.slots.destroy', [$jadwal, $slot]) }}"
                                        data-confirm-title="Hapus Slot"
                                        data-confirm-message="Hapus slot jadwal ini?"
                                        data-reload-page>
                                    <i class="ti ti-trash"></i>
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada slot jadwal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('modals')
@can('akademik.create')
<x-modal id="akademik-slot-modal" title="Tambah Slot Jadwal" size="lg">
    <form data-fetch-form
          data-reload-page
          data-close-modal="akademik-slot-modal"
          action="{{ route('admin.akademik.jadwal-pelajaran.slots.store', $jadwal) }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="form-label" for="slot-day">Hari</label>
                <select name="day_of_week" id="slot-day" class="form-input" required>
                    @foreach($dayLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="slot-start">Mulai</label>
                <input type="time" name="time_start" id="slot-start" class="form-input" required>
            </div>
            <div>
                <label class="form-label" for="slot-end">Selesai</label>
                <input type="time" name="time_end" id="slot-end" class="form-input" required>
            </div>
        </div>
        <div>
            <label class="form-label" for="slot-mapel">Mata Pelajaran</label>
            <select name="mata_pelajaran_id" id="slot-mapel" class="form-input" data-s2 required>
                <option value="">—</option>
                @foreach($mapelOptions as $mapel)
                    <option value="{{ $mapel->id }}">{{ $mapel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="slot-guru">Guru</label>
                <select name="guru_id" id="slot-guru" class="form-input" data-s2>
                    <option value="">—</option>
                    @foreach($guruOptions as $guru)
                        <option value="{{ $guru->id }}">{{ $guru->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="slot-ruang">Ruang</label>
                <input name="ruang" id="slot-ruang" class="form-input" maxlength="255">
            </div>
        </div>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-slot-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endcan
@endpush
