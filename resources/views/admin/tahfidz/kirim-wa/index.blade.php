@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="tahfidz-wa-config" data-build-url="{{ route('admin.tahfidz.kirim-wa.build') }}"></div>

<x-admin.datatable-page
    title="Kirim Rekap via WhatsApp"
    subtitle="Preview pesan per anak lalu buka wa.me ke nomor wali"
    :ajax-url="route('admin.tahfidz.kirim-wa.data')"
    :show-export="false"
    :columns="['NIS', 'Nama', 'Halaqoh', 'Periode', 'Aksi']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-rekap_id">Rekap</label>
                <select name="rekap_id" id="filter-rekap_id" class="form-input">
                    <option value="">Semua</option>
                    @foreach($rekaps as $item)
                        <option value="{{ $item->id }}" @selected((string) request('rekap_id') === (string) $item->id)>
                            {{ $item->program?->label() }} · {{ $item->periodLabel() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="tahfidz-wa-preview-modal" title="Preview Pesan WhatsApp" size="lg">
    <div class="space-y-4">
        <p class="text-sm"><strong>Siswa:</strong> <span id="tahfidz-wa-preview-siswa">-</span></p>
        <p class="text-sm"><strong>Nomor:</strong> <span id="tahfidz-wa-preview-phone">-</span></p>
        <textarea id="tahfidz-wa-preview-message" class="form-input" rows="14" readonly></textarea>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="tahfidz-wa-preview-modal">Tutup</button>
            <a id="tahfidz-wa-open-link" href="#" target="_blank" rel="noopener" class="btn-primary">Buka WhatsApp</a>
        </div>
    </div>
</x-modal>
@endpush

@push('scripts')
<script>
(() => {
    const config = document.getElementById('tahfidz-wa-config');
    const buildUrl = config?.dataset.buildUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.tahfidz-wa-open');
        if (!button || !buildUrl) {
            return;
        }
        event.preventDefault();
        const response = await fetch(buildUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
            body: JSON.stringify({ rekap_siswa_id: Number(button.dataset.rekapSiswaId) }),
        });
        const payload = await response.json();
        if (!response.ok || !payload.success) {
            window.alert(payload.message || 'Gagal membuat pesan WhatsApp.');
            return;
        }
        const data = payload.data || {};
        document.getElementById('tahfidz-wa-preview-siswa').textContent = data.siswa_name || '-';
        document.getElementById('tahfidz-wa-preview-phone').textContent = data.phone_display || '-';
        document.getElementById('tahfidz-wa-preview-message').value = data.message || '';
        const link = document.getElementById('tahfidz-wa-open-link');
        link.setAttribute('href', data.whatsapp_url || '#');
        document.getElementById('tahfidz-wa-preview-modal')?.classList.remove('hidden');
    });
})();
</script>
@endpush
