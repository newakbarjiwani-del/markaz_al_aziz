@extends('layouts.app')

@section('title', $title)

@php
    $rekapPengunjungColumnOptions = [
        ['orderable' => false, 'searchable' => false, 'exportable' => false],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

@section('content')
<div class="visitor-checkin-page">
    <div class="visitor-checkin-page__stats">
        <div class="visitor-checkin-page__stat">
            <div class="visitor-checkin-page__stat-icon">
                <x-icon name="users" />
            </div>
            <div>
                <p class="visitor-checkin-page__stat-label">Pengunjung Hari Ini</p>
                <p class="visitor-checkin-page__stat-value" id="today-visitor-count">{{ $todayCount }}</p>
            </div>
        </div>
        <div class="visitor-checkin-page__stat">
            <div class="visitor-checkin-page__stat-icon">
                <x-icon name="user-check" />
            </div>
            <div>
                <p class="visitor-checkin-page__stat-label">Petugas</p>
                <p class="visitor-checkin-page__stat-value visitor-checkin-page__stat-value--sm">{{ auth()->user()->name }}</p>
            </div>
        </div>
    </div>

    <section class="card">
        <div class="border-b border-slate-200 p-4 dark:border-slate-800">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Catat Kunjungan</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pilih metode dan tipe pengunjung, lalu lengkapi data di bawah.</p>
        </div>

        <div class="space-y-5 p-4">
            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Metode Pencatatan</p>
                    <div class="visitor-segment" id="method-switcher" role="tablist" aria-label="Metode pencatatan">
                        <button type="button" class="visitor-segment__btn is-active method-btn" data-method="manual" role="tab">
                            <x-icon name="list-check" size="sm" /> Manual
                        </button>
                        <button type="button" class="visitor-segment__btn method-btn" data-method="face" role="tab">
                            <x-icon name="camera" size="sm" /> Wajah
                        </button>
                        <button type="button" class="visitor-segment__btn method-btn" data-method="rfid" role="tab">
                            <x-icon name="id" size="sm" /> RFID
                        </button>
                    </div>
                </div>

                <div id="type-switcher-wrap">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tipe Pengunjung</p>
                    <div class="visitor-segment" id="visitor-type-switcher" role="tablist" aria-label="Tipe pengunjung">
                        <button type="button" class="visitor-segment__btn is-active visitor-type-btn" data-type="siswa" role="tab">
                            <x-icon name="school" size="sm" /> Siswa
                        </button>
                        <button type="button" class="visitor-segment__btn visitor-type-btn" data-type="guru" role="tab">
                            <x-icon name="user-star" size="sm" /> Guru
                        </button>
                        <button type="button" class="visitor-segment__btn visitor-type-btn" data-type="karyawan" role="tab">
                            <x-icon name="briefcase" size="sm" /> Karyawan
                        </button>
                        <button type="button" class="visitor-segment__btn visitor-type-btn" data-type="non_siswa" role="tab">
                            <x-icon name="user" size="sm" /> Pengunjung Luar
                        </button>
                    </div>
                </div>
            </div>

            <div class="visitor-workspace">
                {{-- Manual --}}
                <div id="panel-manual">
                    <div id="manual-siswa" class="visitor-form-panel">
                        <div class="visitor-hint">
                            <x-icon name="info-circle" size="sm" class="visitor-hint__icon" />
                            <span>Cari siswa aktif berdasarkan nama atau NIS, lalu simpan kunjungan.</span>
                        </div>
                        <div class="ajax-select-field">
                            <label class="form-label" for="manual_siswa_id">Pilih Siswa</label>
                            <x-ajax-select name="manual_siswa_id" id="manual_siswa_id"
                                :url="route('portal.perpustakaan.rekap-pengunjung.lookup')"
                                :resolve-url="url('portal/perpustakaan/rekap-pengunjung/lookup')"
                                placeholder="Cari nama atau NIS (min. 3 karakter)" />
                        </div>
                        <div>
                            <label class="form-label" for="manual-siswa-catatan">Catatan</label>
                            <input type="text" id="manual-siswa-catatan" class="form-input" maxlength="500" placeholder="Opsional — keperluan kunjungan">
                        </div>
                        <div class="visitor-form-panel__actions">
                            <button type="button" id="manual-siswa-submit" class="btn-primary">
                                <x-icon name="check" size="sm" class="mr-1" /> Catat Kunjungan
                            </button>
                        </div>
                    </div>

                    <div id="manual-guru" class="visitor-form-panel hidden">
                        <div class="visitor-hint">
                            <x-icon name="info-circle" size="sm" class="visitor-hint__icon" />
                            <span>Cari guru aktif berdasarkan nama atau NIP.</span>
                        </div>
                        <div class="ajax-select-field">
                            <label class="form-label" for="manual_guru_id">Pilih Guru</label>
                            <x-ajax-select name="manual_guru_id" id="manual_guru_id"
                                :url="route('portal.perpustakaan.rekap-pengunjung.guru-lookup')"
                                :resolve-url="url('portal/perpustakaan/rekap-pengunjung/guru/lookup')"
                                placeholder="Cari nama atau NIP (min. 3 karakter)" />
                        </div>
                        <div>
                            <label class="form-label" for="manual-guru-catatan">Catatan</label>
                            <input type="text" id="manual-guru-catatan" class="form-input" maxlength="500" placeholder="Opsional">
                        </div>
                        <div class="visitor-form-panel__actions">
                            <button type="button" id="manual-guru-submit" class="btn-primary">
                                <x-icon name="check" size="sm" class="mr-1" /> Catat Kunjungan
                            </button>
                        </div>
                    </div>

                    <div id="manual-karyawan" class="visitor-form-panel hidden">
                        @include('portal.perpustakaan.partials.freeform-visitor-fields', ['prefix' => 'manual-karyawan', 'title' => 'Data Karyawan'])
                        <div class="visitor-form-panel__actions">
                            <button type="button" id="manual-karyawan-submit" class="btn-primary">
                                <x-icon name="check" size="sm" class="mr-1" /> Catat Kunjungan
                            </button>
                        </div>
                    </div>

                    <div id="manual-non-siswa" class="visitor-form-panel hidden">
                        @include('portal.perpustakaan.partials.freeform-visitor-fields', ['prefix' => 'manual-non', 'title' => 'Data Pengunjung Luar'])
                        <div class="visitor-form-panel__actions">
                            <button type="button" id="manual-non-submit" class="btn-primary">
                                <x-icon name="check" size="sm" class="mr-1" /> Catat Kunjungan
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Wajah --}}
                <div id="panel-face" class="hidden">
                    <div id="face-siswa" class="visitor-form-panel--wide">
                        <div class="visitor-hint">
                            <x-icon name="camera" size="sm" class="visitor-hint__icon" />
                            <span>Deteksi wajah siswa yang sudah punya foto referensi. Modal verifikasi muncul sebelum kunjungan disimpan.</span>
                        </div>
                        <div class="face-capture-shell face-siswa-shell">
                            <div class="space-y-3">
                                <div class="face-capture-shell__controls">
                                    <button type="button" id="siswa-face-toggle" class="btn-primary h-11">
                                        <x-icon name="camera" size="sm" class="mr-1" /> Mulai Kamera
                                    </button>
                                    <select id="siswa-face-camera-mode" class="form-input h-11">
                                        <option value="user">Kamera Depan</option>
                                        <option value="environment">Kamera Belakang</option>
                                    </select>
                                </div>
                                <div class="face-capture-frame">
                                    <div class="face-capture-frame__viewport face-capture-frame__viewport--portrait">
                                        <video id="siswa-face-video" autoplay muted playsinline></video>
                                        <canvas id="siswa-face-overlay" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                                    </div>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400" id="siswa-face-status">Siap memulai deteksi wajah.</p>
                            </div>
                            <div class="face-side-panel">
                                <div>
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Deteksi Terakhir</p>
                                    <div id="siswa-face-match-card" class="face-match-card">
                                        <p class="text-slate-500 dark:text-slate-400">Belum ada deteksi.</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Log Sesi</p>
                                    <ul id="siswa-face-log" class="face-log-list"></ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="face-guru" class="visitor-form-panel--wide hidden space-y-4">
                        <div class="visitor-hint">
                            <x-icon name="camera" size="sm" class="visitor-hint__icon" />
                            <span>Pilih guru, ambil foto dokumentasi jika diperlukan, lalu simpan kunjungan.</span>
                        </div>
                        <div class="ajax-select-field max-w-xl">
                            <label class="form-label" for="face_guru_id">Pilih Guru</label>
                            <x-ajax-select name="face_guru_id" id="face_guru_id"
                                :url="route('portal.perpustakaan.rekap-pengunjung.guru-lookup')"
                                :resolve-url="url('portal/perpustakaan/rekap-pengunjung/guru/lookup')"
                                placeholder="Cari nama atau NIP (min. 3 karakter)" />
                        </div>
                        @include('portal.perpustakaan.partials.face-capture-block', ['prefix' => 'guru', 'optional' => true])
                        <div class="max-w-xl">
                            <label class="form-label" for="face-guru-catatan">Catatan</label>
                            <input type="text" id="face-guru-catatan" class="form-input" maxlength="500" placeholder="Opsional">
                        </div>
                        <div class="visitor-form-panel__actions">
                            <button type="button" id="face-guru-submit" class="btn-primary">
                                <x-icon name="check" size="sm" class="mr-1" /> Catat Kunjungan
                            </button>
                        </div>
                    </div>

                    <div id="face-karyawan" class="visitor-form-panel--wide hidden space-y-4">
                        <div class="visitor-hint">
                            <x-icon name="camera" size="sm" class="visitor-hint__icon" />
                            <span>Isi data karyawan. Foto dokumentasi bersifat opsional.</span>
                        </div>
                        @include('portal.perpustakaan.partials.freeform-visitor-fields', ['prefix' => 'face-karyawan', 'title' => 'Data Karyawan'])
                        @include('portal.perpustakaan.partials.face-capture-block', ['prefix' => 'karyawan', 'optional' => true])
                        <div class="visitor-form-panel__actions">
                            <button type="button" id="face-karyawan-submit" class="btn-primary">
                                <x-icon name="check" size="sm" class="mr-1" /> Catat Kunjungan
                            </button>
                        </div>
                    </div>

                    <div id="face-non-siswa" class="visitor-form-panel--wide hidden space-y-4">
                        <div class="visitor-hint">
                            <x-icon name="camera" size="sm" class="visitor-hint__icon" />
                            <span>Isi data pengunjung luar. Foto dokumentasi bersifat opsional.</span>
                        </div>
                        @include('portal.perpustakaan.partials.freeform-visitor-fields', ['prefix' => 'face-non', 'title' => 'Data Pengunjung Luar'])
                        @include('portal.perpustakaan.partials.face-capture-block', ['prefix' => 'non', 'optional' => true])
                        <div class="visitor-form-panel__actions">
                            <button type="button" id="face-non-submit" class="btn-primary">
                                <x-icon name="check" size="sm" class="mr-1" /> Catat Kunjungan
                            </button>
                        </div>
                    </div>
                </div>

                {{-- RFID --}}
                <div id="panel-rfid" class="hidden">
                    <div class="rfid-scan-card">
                        <div class="visitor-hint">
                            <x-icon name="id" size="sm" class="visitor-hint__icon" />
                            <span>Tempelkan kartu RFID pada reader. Sistem mengenali siswa atau guru secara otomatis.</span>
                        </div>
                        <div class="rfid-scan-card__input-wrap">
                            <x-icon name="scan" size="sm" class="rfid-scan-card__input-icon" />
                            <input type="text" id="rfid-input" class="form-input" placeholder="Scan kartu RFID..." autocomplete="off">
                        </div>
                        <div id="rfid-preview" class="rfid-scan-card__preview">
                            Belum ada kartu terbaca.
                        </div>
                        <div>
                            <label class="form-label" for="rfid-catatan">Catatan</label>
                            <input type="text" id="rfid-catatan" class="form-input" maxlength="500" placeholder="Opsional">
                        </div>
                        <button type="button" id="rfid-submit" class="btn-primary w-full sm:w-auto" disabled>
                            <x-icon name="check" size="sm" class="mr-1" /> Catat Kunjungan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="card card--datatable visitor-recap-table">
        <div class="card-divider flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="card-title">Buku Rekap Pengunjung</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Riwayat kunjungan perpustakaan</p>
            </div>
            <div class="datatable-page__toolbar">
                <div class="datatable-page__toolbar-group datatable-page__toolbar-group--export">
                    <x-dropdown-button label="Export" icon="download" variant="secondary" size="sm" menu-label="Export data">
                        <x-dropdown-button.item id="export-excel" icon="file-spreadsheet">Excel</x-dropdown-button.item>
                        <x-dropdown-button.item id="export-pdf" icon="file-type-pdf">PDF</x-dropdown-button.item>
                    </x-dropdown-button>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <form id="filter-form" class="filter-form">
                <div>
                    <label class="form-label" for="filter-date-from">Dari Tanggal</label>
                    <input type="date" id="filter-date-from" name="date_from" class="form-input" value="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="form-label" for="filter-date-to">Sampai Tanggal</label>
                    <input type="date" id="filter-date-to" name="date_to" class="form-input" value="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="form-label" for="filter-visitor-type">Tipe</label>
                    <select id="filter-visitor-type" name="visitor_type" class="form-input">
                        <option value="">Semua</option>
                        <option value="siswa">Siswa</option>
                        <option value="guru">Guru</option>
                        <option value="karyawan">Karyawan</option>
                        <option value="non_siswa">Pengunjung Luar</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="filter-method">Metode</label>
                    <select id="filter-method" name="method" class="form-input">
                        <option value="">Semua</option>
                        <option value="manual">Manual</option>
                        <option value="face">Wajah</option>
                        <option value="rfid">RFID</option>
                    </select>
                </div>
                <x-filter-actions />
            </form>
        </div>

        <div class="card--datatable__body p-4 pt-0">
            <table id="main_table" class="datatable-main w-full display"
                   style="--table-min-width: 55rem"
                   data-ajax-url="{{ $ajaxUrl }}"
                   data-export-filename="Buku Rekap Pengunjung"
                   data-column-options="{{ json_encode($rekapPengunjungColumnOptions) }}">
                <thead>
                    <tr>
                        @foreach(['No', 'Tanggal', 'Jam', 'Tipe', 'Nama', 'NIS/NIP', 'Asal/Kelas', 'Telepon', 'Metode', 'Foto'] as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div id="photo-preview-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="card max-w-md p-4">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-white">Foto Kunjungan</h3>
            <button type="button" id="photo-preview-close" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" aria-label="Tutup">
                <x-icon name="x" size="sm" />
            </button>
        </div>
        <img id="photo-preview-img" src="" alt="Foto kunjungan" class="w-full rounded-lg">
    </div>
</div>
@endsection

@push('scripts')
<script>
window.portalRekapPengunjungConfig = {
    csrfToken: @json(csrf_token()),
    saveUrl: @json(route('portal.perpustakaan.rekap-pengunjung.store')),
    refsUrl: @json(route('portal.perpustakaan.rekap-pengunjung.references')),
    resolveRfidUrl: @json(route('portal.perpustakaan.rekap-pengunjung.resolve-rfid')),
    photoUrlTemplate: @json(url('portal/perpustakaan/rekap-pengunjung/photo/__ID__')),
    modelUrl: 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights',
};
</script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="{{ asset('js/portal-perpustakaan-rekap-pengunjung.js') }}?v=5"></script>
@endpush
