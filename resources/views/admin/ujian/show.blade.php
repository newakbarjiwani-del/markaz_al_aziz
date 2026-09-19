@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.ujian.ujian.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Kembali ke daftar</a>
</div>

<div class="card mb-6 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $ujian->title }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $ujian->tahunAkademik?->name ?? '-' }}
                · {{ \App\Support\AkademikSemester::label($ujian->semester) }}
                · {{ $ujian->kelas?->name ?? 'Semua kelas' }}
                · {{ $ujian->mataPelajaran?->name ?? 'Tanpa mapel' }}
            </p>
            <p class="mt-2">
                <span class="badge {{ $ujian->status === 'published' ? 'badge-green' : ($ujian->status === 'closed' ? 'badge-red' : 'badge-slate') }}">
                    {{ $statusLabels[$ujian->status] ?? $ujian->status }}
                </span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('ujian.update')
                @if($ujian->status !== 'published')
                    <button type="button" class="btn-primary"
                            data-fetch-post="{{ route('admin.ujian.ujian.publish', $ujian) }}"
                            data-reload-page>
                        Publikasikan
                    </button>
                @endif
                @if($ujian->status === 'published')
                    <button type="button" class="btn-secondary"
                            data-fetch-post="{{ route('admin.ujian.ujian.close', $ujian) }}"
                            data-reload-page>
                        Tutup
                    </button>
                @endif
            @endcan
            @can('ujian.create')
                <button type="button" class="btn-primary" data-open-modal="ujian-soal-modal">
                    <x-icon name="plus" size="sm" class="mr-1" /> Tambah Soal
                </button>
            @endcan
        </div>
    </div>
</div>

<div class="space-y-4">
    @forelse($ujian->soal as $index => $soal)
        <div class="card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Soal {{ $index + 1 }} · {{ $jenisLabels[$soal->jenis] ?? $soal->jenis }} · {{ $soal->poin }} poin
                    </p>
                    <p class="mt-2 whitespace-pre-wrap text-slate-900 dark:text-white">{{ $soal->pertanyaan }}</p>
                    @if($soal->isPilihanGanda() && is_array($soal->opsi))
                        <ul class="mt-3 space-y-1 text-sm text-slate-600 dark:text-slate-300">
                            @foreach($soal->opsi as $opsi)
                                <li>
                                    <span class="font-mono font-semibold">{{ $opsi['key'] ?? '?' }}.</span>
                                    {{ $opsi['label'] ?? '' }}
                                    @if(($opsi['key'] ?? null) === $soal->kunci)
                                        <span class="badge badge-green ml-1">Kunci</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @can('ujian.delete')
                    <button type="button" class="btn-secondary text-sm text-red-600"
                            data-fetch-delete="{{ route('admin.ujian.ujian.soal.destroy', [$ujian, $soal]) }}"
                            data-confirm-title="Hapus Soal"
                            data-confirm-message="Hapus soal ini?"
                            data-reload-page>
                        Hapus
                    </button>
                @endcan
            </div>
        </div>
    @empty
        <div class="card p-8 text-center text-slate-500">Belum ada soal.</div>
    @endforelse
</div>
@endsection

@push('modals')
<x-modal id="ujian-soal-modal" title="Tambah Soal" size="lg">
    <form id="ujian-soal-form"
          data-fetch-form
          data-reload-page
          data-close-modal="ujian-soal-modal"
          action="{{ route('admin.ujian.ujian.soal.store', $ujian) }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="ujian-soal-jenis">Jenis</label>
            <select name="jenis" id="ujian-soal-jenis" class="form-input" required>
                @foreach($jenisLabels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="ujian-soal-pertanyaan">Pertanyaan</label>
            <textarea name="pertanyaan" id="ujian-soal-pertanyaan" class="form-input" rows="4" required></textarea>
        </div>
        <div>
            <label class="form-label" for="ujian-soal-poin">Poin</label>
            <input type="number" step="0.01" min="0.01" name="poin" id="ujian-soal-poin" class="form-input" value="1" required>
        </div>
        <div id="ujian-soal-mcq-fields" class="space-y-3">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Opsi pilihan ganda</p>
            @foreach(['A', 'B', 'C', 'D'] as $i => $key)
                <div class="grid gap-2 sm:grid-cols-[4rem_1fr]">
                    <input type="hidden" name="opsi[{{ $i }}][key]" value="{{ $key }}">
                    <span class="form-input flex items-center justify-center font-mono">{{ $key }}</span>
                    <input name="opsi[{{ $i }}][label]" class="form-input" placeholder="Teks opsi {{ $key }}">
                </div>
            @endforeach
            <div>
                <label class="form-label" for="ujian-soal-kunci">Kunci</label>
                <select name="kunci" id="ujian-soal-kunci" class="form-input">
                    @foreach(['A', 'B', 'C', 'D'] as $key)
                        <option value="{{ $key }}">{{ $key }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="ujian-soal-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan Soal</button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script>
(() => {
    const jenis = document.getElementById('ujian-soal-jenis');
    const mcq = document.getElementById('ujian-soal-mcq-fields');
    if (!jenis || !mcq) return;
    const sync = () => {
        mcq.classList.toggle('hidden', jenis.value !== 'pilihan_ganda');
    };
    jenis.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
