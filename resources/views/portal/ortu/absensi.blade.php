@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="ortu-absensi-summary" class="card mb-6 p-5" data-summary-url="{{ route('portal.ortu.absensi.summary') }}">
    <div class="mb-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-primary-200 bg-primary-50 px-4 py-4 dark:border-primary-900/40 dark:bg-primary-950/30">
            <p class="text-muted text-xs font-medium sm:text-sm">Persentase Kehadiran</p>
            <p class="mt-1 text-3xl font-bold text-primary-700 dark:text-primary-300 sm:text-4xl" data-summary-key="kehadiran" data-summary-format="percent">{{ number_format($summary['kehadiran'] ?? 0, 1, ',', '.') }}%</p>
            <p class="text-muted mt-2 text-xs sm:text-sm" data-summary-caption>
                {{ number_format($summary['hadir'] ?? 0) }} hadir dari {{ number_format($summary['total'] ?? 0) }} hari tercatat
            </p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 dark:border-emerald-900/40 dark:bg-emerald-950/30">
            <p class="text-muted text-xs font-medium sm:text-sm">Kehadiran Bulan Ini</p>
            <p class="mt-1 text-3xl font-bold text-emerald-700 dark:text-emerald-300 sm:text-4xl" data-summary-key="kehadiran_bulan_ini" data-summary-format="percent">{{ number_format($summary['kehadiran_bulan_ini'] ?? 0, 1, ',', '.') }}%</p>
            <p class="text-muted mt-2 text-xs sm:text-sm" data-summary-caption-monthly>
                {{ number_format($summary['hadir_bulan_ini'] ?? 0) }} hadir dari {{ number_format($summary['total_bulan_ini'] ?? 0) }} hari · {{ $summary['bulan_ini_label'] ?? '' }}
            </p>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3 lg:grid-cols-5">
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Total</p>
            <p class="mt-1 break-words text-xl font-bold text-primary-700 dark:text-primary-300 sm:text-2xl" data-summary-key="total">{{ number_format($summary['total'] ?? 0) }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Hadir</p>
            <p class="mt-1 break-words text-xl font-bold text-emerald-600 dark:text-emerald-400 sm:text-2xl" data-summary-key="hadir">{{ number_format($summary['hadir'] ?? 0) }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Izin</p>
            <p class="mt-1 break-words text-xl font-bold text-amber-600 dark:text-amber-400 sm:text-2xl" data-summary-key="izin">{{ number_format($summary['izin'] ?? 0) }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Sakit</p>
            <p class="mt-1 break-words text-xl font-bold text-sky-600 dark:text-sky-400 sm:text-2xl" data-summary-key="sakit">{{ number_format($summary['sakit'] ?? 0) }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Alpha</p>
            <p class="mt-1 break-words text-xl font-bold text-rose-600 dark:text-rose-400 sm:text-2xl" data-summary-key="alpha">{{ number_format($summary['alpha'] ?? 0) }}</p>
        </div>
    </div>
</div>

<x-admin.datatable-page
    title="Absensi Anak"
    :ajax-url="route('portal.ortu.absensi.data')"
    :columns="['NIS', 'Nama', 'Kelas', 'Tanggal', 'Status', 'Jam Masuk']"
    :show-export="true">
    <x-slot:filters>
        @include('portal.partials.ortu-datatable-filters', ['children' => $children])
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('scripts')
<script src="{{ asset('js/portal-ortu-absensi.js') }}?v=3"></script>
@endpush
