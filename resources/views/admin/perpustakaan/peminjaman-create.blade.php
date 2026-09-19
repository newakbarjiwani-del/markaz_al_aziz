@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4">
    <a href="{{ $backUrl ?? route('admin.perpustakaan.peminjaman.index') }}" class="btn-secondary inline-flex items-center">
        <x-icon name="arrow-left" size="sm" class="mr-1" /> Kembali ke Daftar
    </a>
</div>

<div class="library-loan-layout mb-6">
    <div class="card p-6">
        <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Pinjam Buku Baru</h2>
        <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
            Masa pinjam default {{ $loanDays ?? 7 }} hari · kuota {{ $maxBooks ?? 3 }} buku aktif per peminjam
        </p>
        <form
            id="loan-borrow-form"
            data-fetch-form
            data-fetch-redirect="{{ $redirectUrl ?? route('admin.perpustakaan.peminjaman.index') }}"
            data-reload-table="false"
            action="{{ $formAction ?? route('admin.perpustakaan.peminjaman.store') }}"
            method="POST"
            class="grid gap-4 md:grid-cols-2"
        >
            @csrf
            <input type="hidden" name="borrower_type" id="loan_borrower_type" value="siswa">

            <div class="md:col-span-2">
                <p class="form-label mb-2">Tipe peminjam</p>
                <div class="visitor-segment" role="tablist" aria-label="Tipe peminjam">
                    <button type="button" class="visitor-segment__btn is-active" data-loan-borrower="siswa">
                        <x-icon name="school" size="sm" class="mr-1" /> Siswa
                    </button>
                    <button type="button" class="visitor-segment__btn" data-loan-borrower="guru">
                        <x-icon name="user-star" size="sm" class="mr-1" /> Guru
                    </button>
                    <button type="button" class="visitor-segment__btn" data-loan-borrower="tamu">
                        <x-icon name="user" size="sm" class="mr-1" /> Tamu
                    </button>
                </div>
            </div>

            <div class="md:col-span-2" data-loan-method-wrap>
                <p class="form-label mb-2">Metode pilih peminjam</p>
                <div class="visitor-segment" role="tablist" aria-label="Metode pilih peminjam">
                    <button type="button" class="visitor-segment__btn is-active" data-loan-method="search">
                        <x-icon name="search" size="sm" class="mr-1" /> Cari
                    </button>
                    <button type="button" class="visitor-segment__btn" data-loan-method="rfid">
                        <x-icon name="nfc" size="sm" class="mr-1" /> Scan RFID
                    </button>
                </div>
            </div>

            <div class="md:col-span-2 hidden" data-loan-panel="rfid">
                <label class="form-label" for="loan-rfid-input">Tempelkan / scan kartu RFID</label>
                <div class="flex gap-2">
                    <input type="text" id="loan-rfid-input" class="form-input" autocomplete="off"
                        placeholder="UID kartu akan terisi otomatis dari alat scan lalu tekan Enter">
                    <button type="button" class="btn-secondary whitespace-nowrap" id="loan-rfid-resolve">
                        <x-icon name="search" size="sm" class="mr-1" /> Cari
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-500" id="loan-rfid-status">Siswa atau guru peminjam otomatis terpilih setelah kartu dikenali.</p>
            </div>

            <div class="md:col-span-2" data-loan-borrower-panel="siswa">
                <x-siswa-select
                    :lookup-url="$siswaLookupUrl ?? null"
                    :lookup-resolve-url="$siswaLookupResolveUrl ?? null"
                />
            </div>

            <div class="md:col-span-2 hidden" data-loan-borrower-panel="guru">
                <x-guru-select
                    :lookup-url="$guruLookupUrl ?? null"
                    :lookup-resolve-url="$guruLookupResolveUrl ?? null"
                    :required="false"
                />
            </div>

            <div class="md:col-span-2 hidden grid gap-4 md:grid-cols-2" data-loan-borrower-panel="tamu">
                <div class="md:col-span-2">
                    <label class="form-label" for="tamu_nama">Nama Tamu <span class="text-red-500">*</span></label>
                    <input type="text" name="tamu_nama" id="tamu_nama" class="form-input" maxlength="255" placeholder="Nama lengkap">
                </div>
                <div>
                    <label class="form-label" for="tamu_asal">Asal / Instansi</label>
                    <input type="text" name="tamu_asal" id="tamu_asal" class="form-input" maxlength="255" placeholder="Sekolah / instansi">
                </div>
                <div>
                    <label class="form-label" for="tamu_telepon">Telepon</label>
                    <input type="text" name="tamu_telepon" id="tamu_telepon" class="form-input" maxlength="30" placeholder="08xxxxxxxxxx">
                </div>
            </div>

            <div class="md:col-span-2" data-loan-books>
                <label class="form-label" for="loan-buku-picker">Buku <span class="text-red-500">*</span></label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <x-buku-select
                            name="buku_picker"
                            id="loan-buku-picker"
                            :label="null"
                            :required="false"
                            :lookup-url="$bukuLookupUrl ?? null"
                            :lookup-resolve-url="$bukuLookupResolveUrl ?? null"
                        />
                    </div>
                    <div class="w-24">
                        <input type="number" id="loan-buku-qty" min="1" value="1" class="form-input" placeholder="Jumlah" aria-label="Jumlah">
                    </div>
                    <button type="button" class="btn-secondary whitespace-nowrap" id="loan-buku-add">
                        <x-icon name="plus" size="sm" class="mr-1" /> Tambah
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    Tambahkan satu atau lebih buku (maksimal sesuai sisa kuota peminjam).
                </p>
                <ul id="loan-buku-list" class="library-loan-book-list mt-3" hidden></ul>
                <div id="loan-buku-ids"></div>
            </div>
            <div>
                <label class="form-label" for="loan_date">Tanggal Pinjam</label>
                <input type="date" name="loan_date" id="loan_date" class="form-input" value="{{ now()->toDateString() }}">
            </div>
            <div>
                <label class="form-label" for="due_date">Jatuh Tempo</label>
                <input type="date" name="due_date" id="due_date" class="form-input">
                <p class="mt-1 text-xs text-slate-500">Default otomatis + {{ $loanDays ?? 7 }} hari, bisa diubah manual</p>
            </div>
            <div class="md:col-span-2">
                <label class="form-label" for="catatan_pinjam">Catatan Peminjaman</label>
                <textarea name="catatan_pinjam" id="catatan_pinjam" rows="2" class="form-input" maxlength="500" placeholder="Opsional — keperluan pinjam, dll."></textarea>
            </div>
            <div class="md:col-span-2 flex flex-wrap gap-2">
                <button type="submit" class="btn-primary">
                    <x-icon name="book-download" size="sm" class="mr-1" /> Simpan Peminjaman
                </button>
                <a href="{{ $backUrl ?? route('admin.perpustakaan.peminjaman.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <aside class="card p-6" id="loan-borrower-summary">
        <h3 class="mb-3 text-base font-semibold text-slate-900 dark:text-white">Ringkasan Peminjam</h3>
        <div class="library-loan-summary-empty text-sm text-slate-500">
            Pilih peminjam untuk melihat peminjaman aktif dan sisa kuota.
        </div>
    </aside>
</div>
@endsection

@push('scripts')
@php
    $libraryLoanConfig = [
        'page' => 'borrow',
        'loanDays' => $loanDays ?? 7,
        'maxBooks' => $maxBooks ?? 3,
        'siswaSummaryUrl' => $siswaSummaryUrl ?? url('admin/perpustakaan/peminjaman/siswa'),
        'guruSummaryUrl' => $guruSummaryUrl ?? url('admin/perpustakaan/peminjaman/guru'),
        'tamuSummaryUrl' => $tamuSummaryUrl ?? route('admin.perpustakaan.peminjaman.tamu-summary'),
        'resolveRfidUrl' => $resolveRfidUrl ?? route('admin.perpustakaan.peminjaman.resolve-rfid'),
    ];
@endphp
<script type="application/json" id="library-loan-config">{!! json_encode($libraryLoanConfig) !!}</script>
<script src="{{ asset('js/library-loan.js') }}?v=6"></script>
@endpush
