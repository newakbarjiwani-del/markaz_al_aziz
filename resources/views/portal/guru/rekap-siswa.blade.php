@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="space-y-5">
    {{-- Header & Tab Navigation --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ $title }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Rekapitulasi presensi harian per mata pelajaran dan log absensi siswa</p>
        </div>

        {{-- View Mode Tabs --}}
        <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800/80">
            <button type="button"
                    data-rekap-tab="daily-subject"
                    class="rekap-tab-btn inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-medium transition-all bg-white text-slate-900 shadow-xs dark:bg-slate-900 dark:text-white">
                <x-icon name="list-check" size="xs" />
                <span>Matriks Per Mapel (Harian)</span>
            </button>
            <button type="button"
                    data-rekap-tab="datatable"
                    class="rekap-tab-btn inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-medium transition-all text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                <x-icon name="table" size="xs" />
                <span>Tabel Log Absensi</span>
            </button>
        </div>
    </div>

    @if(! $hasTeachingAssignment)
        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
            Belum ada jadwal absen aktif yang menugaskan Anda. Rekap absensi siswa akan tampil setelah admin mengatur jadwal absen (bisa mencakup beberapa kelas).
        </div>
    @elseif($classes !== [])
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-semibold text-slate-900 dark:text-white">Kelas / Penugasan Mengajar Anda:</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($classes as $classId => $className)
                    <span class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                        {{ $className }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Tab 1: Daily Subject Matrix View (Primary) --}}
    <div id="tab-daily-subject-content" class="rekap-tab-content">
        <x-attendance.daily-subject-matrix
            id="guru-daily-subject-matrix"
            :ajax-url="$dailySubjectMatrixUrl"
            :classes="$classes" />
    </div>

    {{-- Tab 2: DataTable Log View --}}
    <div id="tab-datatable-content" class="rekap-tab-content hidden">
        <x-admin.datatable-page
            title="Log Detail Absensi Siswa"
            :ajax-url="route('portal.guru.rekap-siswa.data')"
            :columns="['NIS', 'Nama', 'Kelas Siswa', 'Pelajaran', 'Kelas Jadwal', 'Tanggal', 'Status', 'Jam Masuk']"
            :show-export="true">
            <x-slot:filters>
                @include('portal.partials.date-filter')
            </x-slot:filters>
        </x-admin.datatable-page>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/daily-subject-matrix.js') }}?v=2"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabBtns = document.querySelectorAll('.rekap-tab-btn');
    const tabContents = document.querySelectorAll('.rekap-tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const tabName = this.dataset.rekapTab;

            tabBtns.forEach(b => {
                b.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'dark:bg-slate-900', 'dark:text-white');
                b.classList.add('text-slate-600', 'dark:text-slate-400');
            });
            this.classList.add('bg-white', 'text-slate-900', 'shadow-xs', 'dark:bg-slate-900', 'dark:text-white');
            this.classList.remove('text-slate-600', 'dark:text-slate-400');

            tabContents.forEach(content => {
                if (content.id === `tab-${tabName}-content`) {
                    content.classList.remove('hidden');
                } else {
                    content.classList.add('hidden');
                }
            });
        });
    });
});
</script>
@endpush
@endsection
