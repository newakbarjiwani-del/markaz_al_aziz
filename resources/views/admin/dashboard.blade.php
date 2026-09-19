@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="admin-dashboard dashboard-page">
    <x-admin.dashboard-hero
        :greeting="$greeting"
        :greeting-icon="$greetingIcon"
        :user-name="$userName"
        :highlights="$highlights"
    />

    <section class="admin-dashboard-kpis">
        <div class="dashboard-stats">
            @foreach($stats as $stat)
                <x-stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :accent="$stat['accent']"
                    :icon-name="$stat['iconName'] ?? null"
                    :hint="$stat['hint'] ?? null"
                />
            @endforeach
        </div>
    </section>

    <div class="mb-6">
        <x-perizinan.rekap-card :summary="$perizinanSummary" rekap-url="{{ route('portal.perizinan.rekap-laporan') }}" />
    </div>

    <x-admin.module-grid :modules="$modules" />

    @include('admin.partials.dashboard-charts', ['charts' => $charts ?? []])

    <div class="admin-dashboard-main dashboard-grid dashboard-grid--sidebar">
        <div class="card dashboard-table-card admin-dashboard-panel">
            <div class="admin-dashboard-panel__head">
                <div>
                    <h2 class="admin-dashboard-panel__title">Tagihan Terbaru</h2>
                    <p class="admin-dashboard-panel__subtitle">6 tagihan SPP terakhir dicatat sistem</p>
                </div>
                <a href="{{ route('admin.keuangan.tagihan.index') }}" class="btn-secondary text-sm">Kelola Tagihan</a>
            </div>
            <div class="table-scroll">
                <table class="admin-dashboard-table w-full text-sm">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Jenis</th>
                            <th class="text-right">Jumlah</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTagihan as $tagihan)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $tagihan->siswa?->name ?? '-' }}</p>
                                    <p class="text-muted text-xs">{{ $tagihan->siswa?->nis }}</p>
                                </td>
                                <td class="text-muted">{{ $tagihan->jenis }}</td>
                                <td class="text-right font-medium whitespace-nowrap">Rp {{ number_format($tagihan->amount, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $tagihan->statusBadgeClass() }}">{{ $tagihan->statusLabel() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="admin-dashboard-table__empty">Belum ada data tagihan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="admin-dashboard-aside space-y-4">
            <div class="card admin-dashboard-panel p-5">
                <h2 class="admin-dashboard-panel__title">Absensi Hari Ini</h2>
                <p class="admin-dashboard-panel__subtitle">Rekap kehadiran siswa & guru</p>

                @php
                    $attendanceTotal = max(1, $attendanceToday['hadir'] + $attendanceToday['alpha'] + $attendanceToday['izin'] + $attendanceToday['sakit']);
                    $attendanceRows = [
                        ['label' => 'Hadir', 'value' => $attendanceToday['hadir'], 'tone' => 'green'],
                        ['label' => 'Alpha', 'value' => $attendanceToday['alpha'], 'tone' => 'red'],
                        ['label' => 'Izin', 'value' => $attendanceToday['izin'], 'tone' => 'accent'],
                        ['label' => 'Sakit', 'value' => $attendanceToday['sakit'], 'tone' => 'blue'],
                    ];
                @endphp

                <div class="admin-dashboard-attendance mt-4 space-y-3">
                    @foreach($attendanceRows as $row)
                        <div>
                            <div class="admin-dashboard-attendance__row">
                                <span>{{ $row['label'] }}</span>
                                <span class="font-semibold">{{ $row['value'] }}</span>
                            </div>
                            <div class="admin-dashboard-attendance__track">
                                <div @class(['admin-dashboard-attendance__bar', 'admin-dashboard-attendance__bar--'.$row['tone']])
                                     style="width: {{ min(100, ($row['value'] / $attendanceTotal) * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="admin-dashboard-attendance__footer mt-4">
                    <span class="text-muted text-sm">Guru hadir</span>
                    <span class="font-semibold">{{ $attendanceToday['guru_hadir'] }}</span>
                </div>

                <a href="{{ route('admin.absensi.dashboard') }}" class="btn-secondary mt-4 w-full text-center text-sm">Buka Absensi</a>
            </div>

            @if($priorities !== [])
                <div class="card admin-dashboard-panel p-5">
                    <h2 class="admin-dashboard-panel__title">Perlu Perhatian</h2>
                    <p class="admin-dashboard-panel__subtitle">Prioritas operasional hari ini</p>

                    <div class="mt-4 space-y-3">
                        @foreach($priorities as $item)
                            <a href="{{ route($item['route']) }}"
                               @class(['admin-priority-item', 'admin-priority-item--'.$item['tone']])>
                                <span @class(['admin-priority-item__icon', 'admin-priority-item__icon--'.$item['tone']])>
                                    <x-icon :name="$item['icon']" size="sm" />
                                </span>
                                <span class="min-w-0">
                                    <span class="admin-priority-item__title">{{ $item['title'] }}</span>
                                    <span class="admin-priority-item__description">{{ $item['description'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
