@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-6 p-6">
    <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ajukan Tambahan Uang Saku</h2>
    <form data-fetch-form action="{{ route('admin.dompet-digital.pengajuan-tambahan.store') }}" method="POST" class="grid gap-4 md:grid-cols-3">
        @csrf
        <x-siswa-select :status="null" id="pengajuan-siswa" />
        <div>
            <label class="form-label" for="pengajuan-amount">Nominal (Rp)</label>
            <x-form.amount name="amount" id="pengajuan-amount" :min="1000" required />
        </div>
        <div>
            <label class="form-label" for="pengajuan-reason">Alasan</label>
            <input type="text" name="reason" id="pengajuan-reason" class="form-input" placeholder="Keperluan...">
        </div>
        <div class="md:col-span-3">
            <button type="submit" class="btn-primary">Kirim Pengajuan</button>
        </div>
    </form>
</div>

@include('admin.partials.datatable-page', [
    'tableTitle' => 'Daftar Pengajuan',
    'ajaxUrl' => route('admin.dompet-digital.pengajuan-tambahan.data'),
    'columns' => ['NIS', 'Nama', 'Nominal', 'Alasan', 'Status', 'Aksi'],
])

@push('scripts')
<script>
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-approve-url]');
    if (!btn) return;

    const confirmed = await window.showConfirm?.({
        title: 'Konfirmasi Persetujuan',
        message: 'Setujui pengajuan tambahan uang saku ini?',
        tone: 'primary',
        confirmText: 'Ya, Setujui',
    });
    if (!confirmed) return;

    const res = await fetch(btn.dataset.approveUrl, {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
        window.reloadMainTable?.();
        window.showToast?.(data.message, 'success');
    } else {
        await window.showAlert?.({
            title: 'Gagal',
            message: data.message || 'Gagal memproses.',
            variant: 'danger',
        });
    }
});
</script>
@endpush
@endsection
