@extends('layouts.app')

@section('title', $title)

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.akademik.kurikulum.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Kembali ke daftar</a>
</div>

<div class="card mb-6 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $kurikulum->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $kurikulum->tahunAkademik?->name ?? '-' }}
                @if($kurikulum->jenjang) · {{ $kurikulum->jenjang }} @endif
                · {{ $kurikulum->sekolah?->name ?? 'Semua sekolah' }}
            </p>
            @if($kurikulum->description)
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $kurikulum->description }}</p>
            @endif
        </div>
        @can('akademik.create')
            <button type="button" class="btn-primary" data-open-modal="akademik-kurikulum-mapel-modal">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Mapel
            </button>
        @endcan
    </div>
</div>

<div class="space-y-4">
    @forelse($kurikulum->mapel as $mapelRow)
        <div class="card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        {{ $mapelRow->mataPelajaran?->name ?? '-' }}
                        @if($mapelRow->mataPelajaran?->code)
                            <span class="font-mono text-sm text-slate-500">({{ $mapelRow->mataPelajaran->code }})</span>
                        @endif
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Tingkat: {{ $mapelRow->tingkat ?? '-' }}
                        · Jam/minggu: {{ $mapelRow->jam_mingguan ?? '-' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @can('akademik.create')
                        <button type="button" class="btn-secondary text-sm"
                                data-open-modal="akademik-kd-modal"
                                data-kd-mapel-id="{{ $mapelRow->id }}"
                                data-kd-store-url="{{ route('admin.akademik.kurikulum.kd.store', $mapelRow) }}">
                            Tambah KD
                        </button>
                    @endcan
                    @can('akademik.delete')
                        <button type="button" class="btn-secondary text-sm text-red-600"
                                data-fetch-delete="{{ route('admin.akademik.kurikulum.mapel.destroy', [$kurikulum, $mapelRow]) }}"
                                data-confirm-title="Hapus Mapel"
                                data-confirm-message="Hapus mata pelajaran dari kurikulum ini?"
                                data-reload-page>
                            Hapus
                        </button>
                    @endcan
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <th class="px-3 py-2 text-left font-medium text-slate-600">Kode</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-600">Deskripsi</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-600">Semester</th>
                            <th class="px-3 py-2 text-right font-medium text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mapelRow->kompetensiDasar as $kd)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-3 py-2 font-mono">{{ $kd->kode }}</td>
                                <td class="px-3 py-2">{{ $kd->deskripsi }}</td>
                                <td class="px-3 py-2">{{ $kd->semester ? (\App\Support\AkademikSemester::label($kd->semester)) : '-' }}</td>
                                <td class="px-3 py-2 text-right">
                                    @can('akademik.delete')
                                        <button type="button" class="btn-action btn-action-delete"
                                                data-fetch-delete="{{ route('admin.akademik.kurikulum.kd.destroy', [$mapelRow, $kd]) }}"
                                                data-confirm-title="Hapus KD"
                                                data-confirm-message="Hapus kompetensi dasar ini?"
                                                data-reload-page>
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">Belum ada kompetensi dasar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card p-8 text-center text-slate-500">Belum ada mata pelajaran pada kurikulum ini.</div>
    @endforelse
</div>
@endsection

@push('modals')
@can('akademik.create')
<x-modal id="akademik-kurikulum-mapel-modal" title="Tambah Mata Pelajaran">
    <form data-fetch-form
          data-reload-page
          data-close-modal="akademik-kurikulum-mapel-modal"
          action="{{ route('admin.akademik.kurikulum.mapel.store', $kurikulum) }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="kurikulum-mapel-id">Mata Pelajaran</label>
            <select name="mata_pelajaran_id" id="kurikulum-mapel-id" class="form-input" data-s2 required>
                <option value="">—</option>
                @foreach($mapelOptions as $mapel)
                    <option value="{{ $mapel->id }}">{{ $mapel->name }}@if($mapel->code) ({{ $mapel->code }})@endif</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="form-label" for="kurikulum-mapel-tingkat">Tingkat</label>
                <input type="number" name="tingkat" id="kurikulum-mapel-tingkat" class="form-input" min="1" max="12">
            </div>
            <div>
                <label class="form-label" for="kurikulum-mapel-jam">Jam / minggu</label>
                <input type="number" name="jam_mingguan" id="kurikulum-mapel-jam" class="form-input" min="0" max="40">
            </div>
            <div>
                <label class="form-label" for="kurikulum-mapel-sort">Urutan</label>
                <input type="number" name="sort_order" id="kurikulum-mapel-sort" class="form-input" min="0" value="0">
            </div>
        </div>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-kurikulum-mapel-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>

<x-modal id="akademik-kd-modal" title="Tambah Kompetensi Dasar">
    <form id="akademik-kd-form"
          data-fetch-form
          data-reload-page
          data-close-modal="akademik-kd-modal"
          action="#"
          method="POST"
          class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="kd-kode">Kode</label>
            <input name="kode" id="kd-kode" class="form-input" required maxlength="50">
        </div>
        <div>
            <label class="form-label" for="kd-deskripsi">Deskripsi</label>
            <textarea name="deskripsi" id="kd-deskripsi" class="form-input" rows="3" required maxlength="5000"></textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="kd-semester">Semester</label>
                <select name="semester" id="kd-semester" class="form-input">
                    <option value="">—</option>
                    @foreach($semesters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="kd-sort">Urutan</label>
                <input type="number" name="sort_order" id="kd-sort" class="form-input" min="0" value="0">
            </div>
        </div>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-kd-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endcan
@endpush

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-kd-store-url]');
    if (!btn) return;
    var form = document.getElementById('akademik-kd-form');
    if (!form) return;
    form.action = btn.getAttribute('data-kd-store-url');
});
</script>
@endpush
