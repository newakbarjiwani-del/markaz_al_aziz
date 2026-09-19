@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Absensi Siswa', 'route' => 'admin.absensi.absensi-siswa.index', 'icon' => 'clipboard-check', 'tone' => 'success'],
            ['label' => 'Absensi RFID', 'route' => 'admin.absensi.absensi-rfid', 'icon' => 'nfc', 'tone' => 'info'],
            ['label' => 'Absensi Guru', 'route' => 'admin.absensi.absensi-guru.index', 'icon' => 'user-check', 'tone' => 'primary'],
            ['label' => 'Jadwal Absen', 'route' => 'admin.absensi.jadwal-absen.index', 'icon' => 'calendar-event', 'tone' => 'warning'],
            ['label' => 'Jadwal Absensi Guru', 'route' => 'admin.absensi.jadwal-absensi-guru.index', 'icon' => 'calendar-time', 'tone' => 'warning'],
            ['label' => 'Rekap Siswa', 'route' => 'admin.absensi.rekap-presensi', 'icon' => 'report-analytics', 'tone' => 'info'],
            ['label' => 'Rekap Guru', 'route' => 'admin.absensi.rekap-presensi-guru', 'icon' => 'chart-bar', 'tone' => 'purple'],
            ['label' => 'Laporan', 'route' => 'admin.absensi.laporan-absensi', 'icon' => 'file-description', 'tone' => 'neutral'],
        ]"
    />

    <div class="dashboard-stats">
        @foreach($stats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
        @endforeach
    </div>

    @include('admin.partials.dashboard-charts', ['charts' => $charts ?? []])

    <div class="card dashboard-table-card">
        <div class="border-b border-slate-200 p-4 dark:border-slate-800">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Absensi Hari Ini</h2>
        </div>
        <div class="table-scroll">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                        <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Siswa</th>
                        <th class="px-4 py-3 text-center font-medium text-slate-600 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-center font-medium text-slate-600 dark:text-slate-400">Jam Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAbsensi as $row)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $row->siswa?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">{{ ucfirst($row->status) }}</td>
                            <td class="px-4 py-3 text-center">{{ $row->time_in ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada absensi hari ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
