@extends('layouts.attendance-terminal')

@section('title', $title)

@section('content')
@php
    $summary = [
        'total' => count($board),
        'masuk' => collect($board)->where('has_masuk', true)->count(),
        'pulang' => collect($board)->where('has_pulang', true)->count(),
    ];
    $summary['belum'] = max(0, $summary['total'] - $summary['masuk']);
@endphp

<div class="attendance-terminal__shell">
    @if($liburToday)
        <div class="attendance-terminal__alert" role="status">
            <span class="attendance-terminal__alert-icon"><x-icon name="calendar-off" size="md" /></span>
            <div>
                <strong>Hari Libur: {{ $liburToday->name }}</strong><br>
                Absensi RFID dinonaktifkan. Gunakan mode <strong>Manual</strong> untuk izin, sakit, cuti, atau alpha.
            </div>
        </div>
    @endif

    <header class="attendance-terminal__topbar">
        <div class="min-w-0 flex-1">
            <a href="{{ $backUrl }}" class="attendance-terminal__back">
                <x-icon name="arrow-left" size="sm" /> Kembali ke Jadwal
            </a>
            <h1 class="attendance-terminal__title">{{ $jadwal->name }}</h1>
            <p class="attendance-terminal__subtitle">
                {{ $jadwal->sekolah?->name }}
                &bull; {{ \App\Support\DisplayDate::time($jadwal->jam_masuk) }}–{{ \App\Support\DisplayDate::time($jadwal->jam_pulang) }}
                &bull; Toleransi {{ $jadwal->toleransi_menit }} menit
            </p>
        </div>
        <div class="attendance-terminal__stats">
            <div class="attendance-terminal__stat attendance-terminal__stat--masuk">
                <p class="attendance-terminal__stat-label">Masuk</p>
                <p class="attendance-terminal__stat-value" id="summary-masuk">{{ $summary['masuk'] }}</p>
            </div>
            <div class="attendance-terminal__stat attendance-terminal__stat--pulang">
                <p class="attendance-terminal__stat-label">Pulang</p>
                <p class="attendance-terminal__stat-value" id="summary-pulang">{{ $summary['pulang'] }}</p>
            </div>
            <div class="attendance-terminal__stat attendance-terminal__stat--belum">
                <p class="attendance-terminal__stat-label">Belum</p>
                <p class="attendance-terminal__stat-value" id="summary-belum">{{ $summary['belum'] }}</p>
            </div>
            <div class="attendance-terminal__stat attendance-terminal__stat--total">
                <p class="attendance-terminal__stat-label">Total</p>
                <p class="attendance-terminal__stat-value" id="summary-total">{{ $summary['total'] }}</p>
            </div>
        </div>
    </header>

    <div class="attendance-terminal__grid">
        <section class="attendance-terminal__card">
            <div class="attendance-terminal__card-head">
                <div class="attendance-terminal__mode-switch">
                    <button type="button" class="attendance-terminal__mode-btn is-active" data-attendance-mode="rfid">
                        <x-icon name="id" size="sm" /> RFID
                    </button>
                    <button type="button" class="attendance-terminal__mode-btn" data-attendance-mode="manual">
                        <x-icon name="list-check" size="sm" /> Manual
                    </button>
                </div>
            </div>
            <div class="attendance-terminal__card-body">
                <div id="panel-rfid" class="space-y-3">
                    <p class="attendance-terminal__hint">
                        Setiap tap kartu menampilkan konfirmasi. Pilih <strong>Ya</strong> untuk mencatat, atau <strong>Batal</strong> jika tap tidak disengaja.
                    </p>
                    <div class="attendance-terminal__scan-zone">
                        <div class="attendance-terminal__scan-icon">
                            <x-icon name="scan" size="lg" />
                        </div>
                        <label class="form-label mb-2 block text-center" for="rfid-input">UID Kartu RFID</label>
                        <input type="text" id="rfid-input" class="attendance-terminal__scan-input" placeholder="Scan kartu RFID..." autocomplete="off" @if($liburToday) disabled @endif>
                        @if($liburToday)
                            <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">RFID nonaktif pada hari libur.</p>
                        @endif
                    </div>
                    <div id="rfid-preview" class="hidden rounded-xl border border-dashed border-slate-300 p-4 text-sm dark:border-slate-600"></div>
                </div>

                <div id="panel-manual" class="attendance-terminal__manual-form hidden">
                    <p class="attendance-terminal__hint">
                        Pilih guru. Absen pertama dicatat sebagai <strong>masuk</strong>, absen kedua sebagai <strong>pulang</strong>. Jika absen pertama dilakukan setelah jam pulang, sistem otomatis mencatat <strong>masuk + pulang</strong>.
                    </p>
                    <div>
                        <label class="form-label" for="manual-guru">Guru</label>
                        <select id="manual-guru" class="form-input">
                            <option value="">Pilih guru</option>
                            @foreach($board as $row)
                                @if(!$row['is_complete'])
                                    <option value="{{ $row['id'] }}">{{ $row['name'] }} · {{ $row['nip'] ?? '-' }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div id="manual-status-wrap">
                        <label class="form-label" for="manual-status">Status</label>
                        <x-attendance-status-select type="guru" id="manual-status" :include-auto="true" class="form-input w-full max-w-none" />
                        <p class="text-muted mt-1 text-xs">Izin, sakit, cuti, dan alpha tanpa jam masuk/pulang.</p>
                    </div>
                    <div>
                        <label class="form-label" for="manual-notes">Catatan (opsional)</label>
                        <input type="text" id="manual-notes" class="form-input" maxlength="500">
                    </div>
                    <button type="button" id="manual-submit" class="btn-primary w-full sm:w-auto">Simpan Absensi</button>
                </div>
            </div>
        </section>

        <section class="attendance-terminal__card">
            <div class="attendance-terminal__card-head flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Kehadiran Hari Ini</h2>
                    <p class="text-xs text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
                <button type="button" id="refresh-board" class="btn-secondary btn-sm">
                    <x-icon name="refresh" size="sm" class="mr-1" /> Refresh
                </button>
            </div>
            <div class="attendance-terminal__card-body pt-3">
                <div id="attendance-board" class="attendance-terminal__board">
                    @forelse($board as $row)
                        @include('admin.absensi.jadwal-absensi-guru.partials.board-row', ['row' => $row])
                    @empty
                        <p class="attendance-terminal__empty">Belum ada guru pada jadwal ini. Atur guru terlebih dahulu.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.jadwalGuruAttendanceConfig = {
        referencesUrl: @json($referencesUrl),
        storeUrl: @json($storeUrl),
        csrfToken: @json(csrf_token()),
        initialBoard: @json($board),
        initialSummary: @json($summary),
        rfidDisabled: @json((bool) $liburToday),
    };
</script>
<script src="{{ asset('js/jadwal-absensi-guru-attendance.js') }}?v=11"></script>
@endpush
