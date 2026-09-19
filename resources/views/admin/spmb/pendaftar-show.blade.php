@extends('layouts.app')

@section('title', $title)

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.spmb.pendaftar.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Kembali ke daftar</a>
</div>

<div class="card p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-sm text-slate-500">{{ $pendaftar->nomor_pendaftaran }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ $pendaftar->name }}</h1>
            <p class="mt-2">
                <span class="badge {{ match($pendaftar->status) {
                    'accepted' => 'badge-green',
                    'verified' => 'badge-blue',
                    'rejected' => 'badge-red',
                    default => 'badge-amber',
                } }}">{{ $pendaftar->statusLabel() }}</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('spmb.update')
                @if($pendaftar->status === \App\Models\SpmbPendaftar::STATUS_SUBMITTED)
                    <button type="button" id="spmb-verify-btn" class="btn-secondary"
                            data-url="{{ route('admin.spmb.pendaftar.verify', $pendaftar) }}">
                        Verifikasi
                    </button>
                @endif
                @if($pendaftar->status === \App\Models\SpmbPendaftar::STATUS_VERIFIED)
                    <button type="button" class="btn-primary" data-open-modal="spmb-accept-modal">Terima</button>
                    <button type="button" class="btn-secondary" data-open-modal="spmb-reject-modal">Tolak</button>
                @endif
                @if($pendaftar->status === \App\Models\SpmbPendaftar::STATUS_SUBMITTED)
                    <button type="button" class="btn-secondary" data-open-modal="spmb-reject-modal">Tolak</button>
                @endif
            @endcan
        </div>
    </div>

    <dl class="mt-8 grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Periode</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $pendaftar->periode?->name ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Sekolah</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $pendaftar->sekolah?->name ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Jenis Kelamin</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $pendaftar->gender === 'L' ? 'Laki-laki' : ($pendaftar->gender === 'P' ? 'Perempuan' : '-') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">TTL</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">
                {{ $pendaftar->birth_place ?: '-' }}
                @if($pendaftar->birth_date), {{ $pendaftar->birth_date->translatedFormat('d M Y') }}@endif
            </dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Alamat</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $pendaftar->address ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">HP Calon</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $pendaftar->phone ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Orang Tua</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $pendaftar->parent_name ?: '-' }} · {{ $pendaftar->parent_phone ?: '-' }}</dd>
        </div>
        @if($pendaftar->notes)
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Catatan</dt>
                <dd class="mt-1 text-slate-900 dark:text-white">{{ $pendaftar->notes }}</dd>
            </div>
        @endif
        @if($pendaftar->siswa)
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Siswa terkait</dt>
                <dd class="mt-1 text-slate-900 dark:text-white">
                    {{ $pendaftar->siswa->name }} · NIS {{ $pendaftar->siswa->nis }}
                    @if($pendaftar->siswa->kelas) · {{ $pendaftar->siswa->kelas->name }}@endif
                </dd>
            </div>
        @endif
    </dl>
</div>
@endsection

@push('modals')
<x-modal id="spmb-accept-modal" title="Terima Pendaftar">
    <form id="spmb-accept-form"
          data-fetch-form
          data-reload-page
          data-default-action="{{ route('admin.spmb.pendaftar.accept', $pendaftar) }}"
          action="{{ route('admin.spmb.pendaftar.accept', $pendaftar) }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="spmb-accept-nis">NIS</label>
                    <input type="text" name="nis" id="spmb-accept-nis" class="form-input" required inputmode="numeric" pattern="[0-9]+" maxlength="30">
                </div>
                <div>
                    <label class="form-label" for="spmb-accept-kelas_id">Kelas (opsional)</label>
                    <select name="kelas_id" id="spmb-accept-kelas_id" class="form-input" data-s2>
                        <option value="">—</option>
                        @foreach($kelasList as $kelas)
                            <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="spmb-accept-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">Terima & Buat Siswa</button>
        </div>
    </form>
</x-modal>

<x-modal id="spmb-reject-modal" title="Tolak Pendaftar">
    <form id="spmb-reject-form"
          data-fetch-form
          data-reload-page
          data-default-action="{{ route('admin.spmb.pendaftar.reject', $pendaftar) }}"
          action="{{ route('admin.spmb.pendaftar.reject', $pendaftar) }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="spmb-reject-notes">Catatan (opsional)</label>
                    <textarea name="notes" id="spmb-reject-notes" class="form-input" rows="3" maxlength="5000"></textarea>
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="spmb-reject-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">Tolak</button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script>
document.getElementById('spmb-verify-btn')?.addEventListener('click', async function () {
    if (!window.confirm('Verifikasi pendaftar ini?')) return;
    const url = this.dataset.url;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    const json = await res.json().catch(() => ({}));
    if (res.ok && json.success) {
        window.location.reload();
        return;
    }
    alert(json.message || 'Gagal memverifikasi.');
});
</script>
@endpush
