@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="space-y-5">
    {{-- Header & Tab Navigation --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ $title }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Rekapitulasi presensi harian per mata pelajaran dan akumulasi seluruh siswa</p>
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
                    data-rekap-tab="subject-period"
                    class="rekap-tab-btn inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-medium transition-all text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                <x-icon name="table" size="xs" />
                <span>Rekap Akumulasi Per Mapel & Periode</span>
            </button>
        </div>
    </div>

    {{-- Tab 1: Daily Subject Matrix View (Primary) --}}
    <div id="tab-daily-subject-content" class="rekap-tab-content">
        <x-attendance.daily-subject-matrix
            id="admin-daily-subject-matrix"
            :ajax-url="$dailySubjectMatrixUrl ?? route('admin.absensi.rekap-presensi.daily-subject-matrix')"
            :classes="$classes ?? []" />
    </div>

    {{-- Tab 2: Subject & Period Summary View --}}
    <div id="tab-subject-period-content" class="rekap-tab-content hidden">
        <x-attendance.subject-period-summary
            id="admin-subject-period-summary"
            :ajax-url="$subjectPeriodSummaryUrl ?? route('admin.absensi.rekap-presensi.subject-period-summary')"
            :classes="$classes ?? []"
            :pelajarans="$pelajarans ?? []" />
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/daily-subject-matrix.js') }}?v=2"></script>
<script src="{{ asset('js/subject-period-summary.js') }}?v=3"></script>
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
