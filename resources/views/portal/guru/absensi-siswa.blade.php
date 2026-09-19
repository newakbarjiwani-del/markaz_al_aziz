@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <div class="stat-card p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Jadwal Hari Ini</p>
        <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ count($schedules) }}</p>
    </div>
    <div class="stat-card p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Absensi Hari Ini</p>
        <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white" id="today-attendance-count">{{ $todayCount }}</p>
    </div>
    <div class="stat-card p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Guru</p>
        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $guru->name }}</p>
    </div>
</div>

<section id="schedule-picker" class="card {{ count($schedules) === 0 ? '' : '' }}">
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Jadwal Absen Hari Ini</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">Pilih jadwal yang Anda ampu, lalu tentukan metode absensi.</p>
    </div>
    <div class="p-4">
        @if($schedules === [])
            <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                Belum ada jadwal absen aktif untuk Anda hari ini. Hubungi admin untuk mengatur jadwal di menu Absensi.
            </div>
        @else
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($schedules as $schedule)
                    <button type="button"
                            class="jadwal-pick-card text-left"
                            data-schedule="{{ \App\Support\EditRecordPayload::encode($schedule) }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">{{ $schedule['pelajaran'] }}</p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $schedule['jadwal_name'] }}</p>
                            </div>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $schedule['is_active_window'] ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $schedule['is_active_window'] ? 'Sesi aktif' : 'Di luar jam' }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $schedule['day_label'] }} · {{ $schedule['time_start'] }}–{{ $schedule['time_end'] }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Toleransi {{ $schedule['tolerance_minutes'] }} menit · {{ $schedule['student_count'] }} siswa</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $schedule['assignment_label'] }}</p>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section id="attendance-workspace" class="mt-4 hidden space-y-4">
    <div class="card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-800">
            <div>
                <button type="button" id="back-to-schedules" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400">
                    <x-icon name="arrow-left" size="sm" /> Kembali ke jadwal
                </button>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white" id="active-schedule-title">-</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400" id="active-schedule-meta">-</p>
            </div>
            <div class="flex flex-wrap gap-2" id="mode-switcher">
                <button type="button" class="btn-secondary btn-sm attendance-mode-btn" data-mode="rfid">
                    <x-icon name="id" size="sm" class="mr-1" /> RFID
                </button>
                <button type="button" class="btn-secondary btn-sm attendance-mode-btn" data-mode="manual">
                    <x-icon name="list-check" size="sm" class="mr-1" /> Manual
                </button>
                <button type="button" class="btn-secondary btn-sm attendance-mode-btn" data-mode="face">
                    <x-icon name="camera" size="sm" class="mr-1" /> Wajah
                </button>
                <button type="button" id="session-exemption-toggle" class="btn-warning btn-sm hidden border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                    <x-icon name="calendar-off" size="sm" class="mr-1" /> Sesi tidak diabsen
                </button>
                <button type="button" id="session-exemption-revoke" class="btn-secondary btn-sm hidden border-emerald-300 text-emerald-800 dark:border-emerald-800 dark:text-emerald-300">
                    <x-icon name="calendar-check" size="sm" class="mr-1" /> Batalkan pengecualian
                </button>
            </div>
        </div>

        <div id="session-exemption-banner" class="mx-4 mt-0 hidden rounded-xl border border-amber-300 bg-amber-50/90 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-start gap-3">
                <div class="rounded-lg bg-amber-500 p-2 text-white shadow-sm">
                    <i class="ti ti-calendar-off text-xl leading-none"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">Sesi Tidak Wajib Absen</h4>
                    <p class="text-xs text-amber-800 dark:text-amber-300" id="session-exemption-banner-text">Sesi ini ditandai tidak diabsen. Santri tidak dihitung alpha di rekap.</p>
                </div>
            </div>
        </div>

        <div class="p-4">
            @if($showGuruAttendancePanel)
                <div class="mb-4 rounded-xl border border-primary-200 bg-primary-50/40 p-4 dark:border-primary-900/40 dark:bg-primary-900/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Absensi Guru (Saya)</p>
                            <span id="guru-attendance-badge" class="mt-1 inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300">Belum Absen</span>
                            <p class="mt-1 text-sm text-slate-700 dark:text-slate-200" id="guru-attendance-status-text">Memuat status absensi guru hari ini...</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cukup absen satu kali per hari. Status ini berlaku di semua jadwal Anda hari ini.</p>
                        </div>
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="min-w-[11rem]">
                                <label for="guru-rfid-input" class="form-label">UID RFID Guru</label>
                                <input type="text" id="guru-rfid-input" class="form-input h-10 font-mono" placeholder="Scan UID guru">
                            </div>
                            <button type="button" id="guru-attendance-rfid" class="btn-secondary btn-sm">
                                <x-icon name="id" size="sm" class="mr-1" /> Absen Guru RFID
                            </button>
                            <button type="button" id="guru-attendance-manual" class="btn-primary btn-sm">
                                <x-icon name="check" size="sm" class="mr-1" /> Absen Masuk Manual
                            </button>
                        </div>
                    </div>
    </div>
@endif

            <div id="mode-rfid" class="attendance-mode-panel hidden max-w-2xl space-y-4">
                <p class="text-sm text-slate-600 dark:text-slate-300">Tempelkan kartu RFID pada reader. UID akan otomatis terbaca dan absensi langsung diproses.</p>
                <div class="rounded-xl border border-dashed border-primary-300 bg-primary-50/40 p-4 dark:border-primary-800 dark:bg-primary-900/10">
                    <label class="form-label">UID Kartu RFID</label>
                    <input type="text" id="rfid-input" class="form-input font-mono" placeholder="Scan kartu RFID..." autocomplete="off">
                </div>
            </div>

            <div id="mode-manual" class="attendance-mode-panel hidden">
                <p class="text-sm text-slate-600 dark:text-slate-300">Gunakan tabel daftar siswa di bawah untuk input satuan atau jamak.</p>
            </div>

            <div id="mode-face" class="attendance-mode-panel hidden">
                <div class="grid gap-4 lg:grid-cols-12">
                    <div class="space-y-4 lg:col-span-8">
                        <div class="grid gap-2 sm:grid-cols-3">
                            <button type="button" id="face-toggle" class="btn-primary h-11 sm:col-span-1">
                                <x-icon name="camera" size="sm" class="mr-1" /> Mulai Kamera
                            </button>
                            <select id="face-camera-mode" class="form-input h-11">
                                <option value="user">Kamera Depan</option>
                                <option value="environment">Kamera Belakang</option>
                            </select>
                            <select id="face-status" class="form-input h-11">
                                <option value="hadir">Status: Hadir</option>
                                <option value="terlambat">Status: Terlambat</option>
                                <option value="izin">Status: Izin</option>
                                <option value="sakit">Status: Sakit</option>
                            </select>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-900 to-slate-950 p-2.5 dark:border-slate-800">
                            <div class="relative mx-auto w-full max-w-md overflow-hidden rounded-[1.75rem] border border-white/15 bg-black lg:max-w-none">
                                <div class="relative aspect-[3/4] sm:aspect-video lg:aspect-[16/10]">
                                    <video id="face-video" class="h-full w-full object-cover" autoplay muted playsinline></video>
                                    <canvas id="face-overlay" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400" id="face-engine-status">Siap dipakai</p>
                    </div>
                    <div class="lg:col-span-4">
                        <div id="face-match-card" class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/40">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada deteksi.</p>
                        </div>
                        <h4 class="mb-2 mt-4 text-sm font-semibold text-slate-900 dark:text-white">Log Sesi Ini</h4>
                        <ul id="face-log-list" class="max-h-72 space-y-2 overflow-y-auto"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden" id="student-list-card">
        <div class="border-b border-slate-200 bg-gradient-to-r from-primary-50 via-white to-slate-50 p-4 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Daftar Siswa Jadwal Ini</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Status kehadiran hari ini ditampilkan untuk semua metode (manual, RFID, wajah).</p>
                </div>
                <div id="student-load-status"
                     class="hidden items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 dark:border-primary-800 dark:bg-primary-900/30 dark:text-primary-300"
                     role="status"
                     aria-live="polite">
                    <i class="ti ti-loader-2 animate-spin text-base leading-none" aria-hidden="true"></i>
                    <span id="student-load-status-text">Memuat siswa...</span>
                </div>
            </div>
        </div>
        <div class="relative space-y-4 p-4" id="student-list-body">
            {{-- Do not combine `hidden` + `flex`/`inline-flex` (Tailwind v4: last display utility wins). --}}
            <div id="student-loading-panel"
                 class="absolute inset-0 z-20 hidden items-center justify-center rounded-b-xl bg-white/80 p-6 backdrop-blur-[2px] dark:bg-slate-950/75"
                 aria-hidden="true">
                <div class="max-w-sm rounded-2xl border border-slate-200 bg-white px-5 py-4 text-center shadow-lg dark:border-slate-700 dark:bg-slate-900">
                    <i class="ti ti-loader-2 mb-3 inline-block animate-spin text-3xl text-primary-600 dark:text-primary-400" aria-hidden="true"></i>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white" id="student-loading-title">Memuat daftar siswa</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" id="student-loading-detail">Mohon tunggu, data sedang diambil dari server...</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total Siswa</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white" id="manual-summary-total">0</p>
                </div>
                <div class="rounded-xl border border-green-200 bg-gradient-to-br from-green-50 to-white px-4 py-3 shadow-sm dark:border-green-900/40 dark:from-green-900/20 dark:to-slate-900">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-green-700 dark:text-green-300">Sudah Absen</p>
                    <p class="mt-1 text-2xl font-bold text-green-700 dark:text-green-300" id="manual-summary-hadir">0</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white px-4 py-3 shadow-sm dark:border-amber-900/40 dark:from-amber-900/20 dark:to-slate-900">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Belum Absen</p>
                    <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300" id="manual-summary-belum">0</p>
                </div>
            </div>

            <div id="student-perizinan-banner" class="hidden rounded-xl border border-amber-300 bg-amber-50/90 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-amber-500 p-2 text-white shadow-sm">
                            <i class="ti ti-file-text text-xl leading-none"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200" id="perizinan-banner-title">Siswa Dalam Perizinan Aktif</h4>
                            <p class="text-xs text-amber-800 dark:text-amber-300" id="perizinan-banner-detail">Siswa yang sedang izin ditempatkan di paling atas tabel untuk kemudahan approve.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="perizinan-banner-approve-btn" class="btn-warning btn-sm border border-amber-400 bg-amber-600 font-semibold text-white hover:bg-amber-700 shadow-sm">
                            <i class="ti ti-check mr-1 text-base leading-none"></i> Approve Semua Izin
                        </button>
                    </div>
                </div>
            </div>

            <div id="student-filter-bar" class="hidden space-y-3 rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/30">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Filter Daftar Siswa</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400" id="student-filter-meta">Memuat...</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="student-filter-select-unmarked" class="btn-secondary btn-sm hidden">
                            Pilih belum absen
                        </button>
                        <button type="button" id="student-filter-reset" class="btn-secondary btn-sm">
                            Reset filter
                        </button>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-4">
                        <label for="student-filter-search" class="form-label">Cari nama / NIS</label>
                        <input type="search" id="student-filter-search" class="form-input" placeholder="Ketik nama atau NIS..." autocomplete="off">
                    </div>
                    <div id="student-filter-sekolah-wrap" class="hidden xl:col-span-3">
                        <label for="student-filter-sekolah" class="form-label">Sekolah</label>
                        <select id="student-filter-sekolah" class="form-input">
                            <option value="">Semua sekolah</option>
                        </select>
                    </div>
                    <div class="xl:col-span-3">
                        <label for="student-filter-kelas" class="form-label">Kelas</label>
                        <select id="student-filter-kelas" class="form-input">
                            <option value="">Semua kelas</option>
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="student-filter-status" class="form-label">Status absen</label>
                        <select id="student-filter-status" class="form-input">
                            <option value="">Semua</option>
                            <option value="hadir">Sudah absen</option>
                            <option value="belum">Belum absen</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="manual-bulk-controls" class="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/30">
                <div class="min-w-[14rem] flex-1">
                    <label class="form-label" for="manual-bulk-status">Status Bulk</label>
                    <x-attendance-status-select type="siswa" id="manual-bulk-status" name="status" />
                </div>
                <button type="button" id="manual-approve-all-perizinan" class="btn-secondary hidden border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    <i class="ti ti-check text-base leading-none mr-1"></i> <span id="manual-approve-all-perizinan-label">Approve Semua Izin</span>
                </button>
                <button type="button" id="manual-submit-selected" class="btn-primary">Simpan Terpilih</button>
            </div>

            <div class="max-h-[34rem] overflow-auto rounded-xl border border-slate-200 shadow-sm dark:border-slate-700">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur dark:bg-slate-900/95">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th id="manual-th-check" class="px-3 py-2 w-10">
                                <input type="checkbox" id="manual-check-all" class="form-checkbox" aria-label="Pilih semua siswa yang tampil" title="Pilih semua yang tampil">
                            </th>
                            <th class="px-3 py-2">Siswa</th>
                            <th class="px-3 py-2">Status Hari Ini</th>
                            <th id="manual-th-input" class="px-3 py-2">Input Status</th>
                            <th id="manual-th-action" class="px-3 py-2 w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="manual-table-body">
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-slate-500">
                                <span class="inline-flex items-center gap-2">
                                    <i class="ti ti-loader-2 animate-spin text-lg text-primary-600" aria-hidden="true"></i>
                                    Memuat daftar siswa...
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
@if(count($schedules) > 0)
<script>
window.portalGuruAbsensiConfig = {
    csrfToken: @json(csrf_token()),
    refsUrl: @json(url()->route('portal.guru.absensi-siswa.references', absolute: false)),
    studentsUrl: @json(url()->route('portal.guru.absensi-siswa.students', absolute: false)),
    saveUrl: @json(url()->route('portal.guru.absensi-siswa.store', absolute: false)),
    guruAttendanceUrl: @json(url()->route('portal.guru.absensi-siswa.guru-attendance', absolute: false)),
    guruAttendanceStoreUrl: @json(url()->route('portal.guru.absensi-siswa.guru-attendance.store', absolute: false)),
    sessionExemptionStoreUrl: @json(url()->route('portal.guru.absensi-siswa.sesi-pengecualian.store', absolute: false)),
    sessionExemptionDestroyUrl: @json(url()->route('portal.guru.absensi-siswa.sesi-pengecualian.destroy', absolute: false)),
    modelUrl: 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights',
};
</script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="{{ asset('js/portal-guru-absensi.js') }}?v=20"></script>
@endif
@endpush
