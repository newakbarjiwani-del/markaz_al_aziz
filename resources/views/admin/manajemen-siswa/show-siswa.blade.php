@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/id-card.css') }}?v=19">
@endpush

@section('content')
@php
    $extra = $siswa->profil?->extra_fields ?? [];
    $hasFotoWajah = $siswa->hasFotoWajah();
    $dailyLimit = $siswa->daily_transaction_limit;
@endphp

<div class="page-toolbar mb-4">
    <a href="{{ route('admin.manajemen-siswa.data-siswa.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400">
        <x-icon name="arrow-left" size="sm" /> Kembali ke Data Siswa
    </a>
    <div class="page-toolbar__actions">
        <x-student-show-actions
            :siswa="$siswa"
            :has-foto-wajah="$hasFotoWajah"
            :siswa-portal-token-active="$siswaPortalTokenActive"
            :ortu-portal-token-active="$ortuPortalTokenActive"
            :has-orang-tua="$hasOrangTua"
        />
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card p-6 lg:col-span-1">
        <div class="mx-auto mb-4 flex h-32 w-32 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
            @if($siswa->profil?->photoUrl())
                <img src="{{ $siswa->profil->photoUrl() }}" alt="{{ $siswa->name }}" class="h-full w-full object-cover">
            @else
                <x-icon name="user" size="lg" class="text-slate-400" />
            @endif
        </div>
        <p class="text-muted mt-2 text-center text-xs font-medium uppercase tracking-wide">Foto Profil</p>
        <h1 class="text-center text-xl font-semibold text-slate-900 dark:text-white">{{ $siswa->name }}</h1>
        <p class="text-muted mt-1 text-center text-sm font-mono">NIS {{ $siswa->nis }}</p>
        <div class="mt-4 flex flex-wrap justify-center gap-2">
            <span class="badge badge-neutral">{{ $siswa->kelas?->name ?? 'Tanpa kelas' }}</span>
            <span class="badge badge-{{ $siswa->status === \App\Models\Siswa::STATUS_ACTIVE ? 'success' : ($siswa->status === \App\Models\Siswa::STATUS_PENDING ? 'warning' : 'danger') }}">
                {{ $siswa->statusLabel() }}
            </span>
        </div>
        <p class="text-muted mt-4 text-center text-xs">
            Rekam wajah (absensi): {{ $hasFotoWajah ? 'Sudah direkam' : 'Belum direkam' }}
        </p>
        <p class="text-muted mt-1 text-center text-xs">
            Foto profil untuk kartu pelajar dapat diubah lewat <strong>Edit</strong>.
        </p>
    </div>

    <div class="card p-6 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Informasi Siswa</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">No. Virtual Account</dt>
                <dd class="mt-1 font-mono text-sm">{{ $siswa->virtualAccountNumber() ?? '-' }}</dd>
                @if(! empty($vaSuffixWarning))
                    <div class="alert alert-warning mt-2" role="alert">
                        <div class="flex items-start gap-2">
                            <x-icon name="alert-triangle" size="sm" class="mt-0.5 shrink-0" />
                            <p class="text-sm">{{ $vaSuffixWarning }}</p>
                        </div>
                    </div>
                @endif
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Sekolah</dt>
                <dd class="mt-1 text-sm">{{ $siswa->sekolah?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Kamar</dt>
                <dd class="mt-1 text-sm">{{ $siswa->kamar?->displayLabel() ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Status Santri</dt>
                <dd class="mt-1 text-sm">{{ $siswa->statusSantri?->nama ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Jenis Kelamin</dt>
                <dd class="mt-1 text-sm">{{ $siswa->gender === 'P' ? 'Perempuan' : ($siswa->gender === 'L' ? 'Laki-laki' : '-') }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Tanggal Lahir</dt>
                <dd class="mt-1 text-sm">{{ $siswa->birth_date?->format('d/m/Y') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Tempat Lahir</dt>
                <dd class="mt-1 text-sm">{{ $siswa->birth_place ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">RFID Cashless</dt>
                <dd class="mt-1 font-mono text-sm">{{ $siswa->rfidUid() ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Status RFID</dt>
                <dd class="mt-1 text-sm">{{ $siswa->isRfidBlocked() ? 'Diblokir' : 'Aktif' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Limit Transaksi Harian</dt>
                <dd class="mt-1 text-sm">
                    @if($dailyLimit !== null)
                        Rp {{ number_format((float) $dailyLimit, 0, ',', '.') }}
                    @else
                        Mengikuti pengaturan global
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-muted text-xs uppercase tracking-wide">Alamat</dt>
                <dd class="mt-1 text-sm">{{ $siswa->address ?? '-' }}</dd>
            </div>
        </dl>
    </div>
</div>

<div class="card mt-6 p-6">
    @include('admin.partials.student-id-card-panel', [
        'siswa' => $siswa,
        'kartu' => $siswa->kartuAktif,
    ])
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Saldo</h2>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-muted text-xs uppercase tracking-wide">Saldo Keuangan</dt>
                <dd class="mt-1 text-sm font-semibold">Rp {{ number_format($siswa->saldoKeuangan?->balance ?? 0, 0, ',', '.') }}</dd>
            </div>
        </dl>

        <h3 class="mb-3 mt-6 text-sm font-semibold text-slate-700 dark:text-slate-200">Saldo Cashless</h3>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Uang Saku</dt>
                <dd class="mt-1 text-sm font-semibold">Rp {{ number_format($siswa->dompet?->saldo_us ?? 0, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Dompet Kantin</dt>
                <dd class="mt-1 text-sm font-semibold">Rp {{ number_format($siswa->dompet?->saldo_kantin ?? 0, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Tabungan</dt>
                <dd class="mt-1 text-sm font-semibold">Rp {{ number_format($siswa->dompet?->saldo_tabungan ?? 0, 0, ',', '.') }}</dd>
            </div>
        </dl>
    </div>

    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Profil Tambahan</h2>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Nama Panggilan</dt>
                <dd class="mt-1 text-sm">{{ $extra['nama_panggilan'] ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Golongan Darah</dt>
                <dd class="mt-1 text-sm">{{ $extra['golongan_darah'] ?? '-' }}</dd>
            </div>
        </dl>
    </div>
</div>

<div class="card mt-6 p-6">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Orang Tua / Wali</h2>
        <button type="button"
                class="btn-primary btn-sm"
                data-siswa-orang-tua
                data-siswa-id="{{ $siswa->id }}"
                data-siswa-orang-tua-url="{{ route('admin.manajemen-siswa.data-siswa.orang-tua.index', $siswa) }}"
                data-siswa-orang-tua-reload="page">
            <x-icon name="users" size="sm" class="mr-1" /> Kelola
        </button>
    </div>
    @forelse($siswa->orangTua as $orangTua)
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 py-4 last:border-0 dark:border-slate-800">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">{{ $orangTua->displayName() }}</p>
                <p class="text-muted mt-1 text-sm">Ayah: {{ $orangTua->nama_ayah ?? '-' }} · {{ $orangTua->telepon_ayah ?? '-' }}</p>
                <p class="text-muted text-sm">Ibu: {{ $orangTua->nama_ibu ?? '-' }} · {{ $orangTua->telepon_ibu ?? '-' }}</p>
                @if($orangTua->nama_wali)
                    <p class="text-muted text-sm">Wali: {{ $orangTua->nama_wali }} · {{ $orangTua->telepon_wali ?? '-' }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.manajemen-siswa.orang-tua.show', $orangTua) }}" class="btn-secondary btn-sm">
                    <x-icon name="eye" size="sm" class="mr-1" /> Detail
                </a>
                @can('students.delete')
                    @php
                        $unlinkConfirmDetail = [
                            ['label' => 'Orang tua', 'value' => $orangTua->displayName()],
                            ['label' => 'Siswa', 'value' => $siswa->name],
                        ];
                    @endphp
                    <button type="button"
                            class="btn-danger btn-sm"
                            data-fetch-delete="{{ route('admin.manajemen-siswa.data-siswa.orang-tua.remove', [$siswa, $orangTua]) }}"
                            data-confirm-title="Hapus Keterkaitan"
                            data-confirm-message="Orang tua ini akan dilepas dari siswa."
                            data-confirm-detail="{{ \App\Support\ConfirmDetail::attr($unlinkConfirmDetail) }}"
                            data-confirm-text="Ya, Hapus"
                            data-confirm-tone="danger"
                            data-confirm-icon="ti-unlink"
                            data-confirm-header-icon="ti-unlink"
                            data-reload-page
                            title="Hapus keterkaitan">
                        <x-icon name="trash" size="sm" />
                    </button>
                @endcan
            </div>
        </div>
    @empty
        <p class="text-muted text-sm">Belum ada data orang tua terhubung.</p>
    @endforelse
</div>
@endsection

@push('modals')
@include('admin.manajemen-siswa.partials.student-form-modal', [
    'classes' => $classes,
    'kamarList' => $kamarList,
    'statusSantriList' => $statusSantriList,
    'reloadPage' => true,
])
@include('admin.manajemen-siswa.partials.siswa-orang-tua-modal')
<x-face-capture-modal />
@endpush

@push('scripts')
<script>
    window.__idCardPrintAssets = {
        css: @json(asset('css/id-card.css') . '?v=19'),
        icons: @json('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css'),
        font: @json('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap'),
        logo: @json(asset('logo.png')),
    };
</script>
<script src="{{ asset('js/id-card.js') }}?v=5"></script>
<script src="{{ asset('js/portal-access.js') }}?v=7"></script>
<script src="{{ asset('js/face-capture.js') }}?v=4"></script>
<script src="{{ asset('js/siswa-orang-tua.js') }}?v=3"></script>
@include('admin.manajemen-siswa.partials.student-form-scripts')
<script>
window.initFaceCapture({
    csrfToken: @json(csrf_token()),
    storeUrl: @json(route('admin.manajemen-siswa.data-siswa.rekam-wajah.store', ['siswa' => '__SISWA__'])),
    deleteUrl: @json(route('admin.manajemen-siswa.data-siswa.rekam-wajah.destroy', ['siswa' => '__SISWA__'])),
    fotoUrl: @json(route('admin.manajemen-siswa.data-siswa.foto-wajah', ['siswa' => '__SISWA__'])),
    openTriggerSelector: '[data-open-face-capture]',
    onSaved: function () {
        window.location.reload();
    },
    onDeleted: function () {
        window.location.reload();
    },
});
</script>
@endpush
