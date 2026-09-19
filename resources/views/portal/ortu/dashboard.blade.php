@extends('layouts.app')

@section('title', '')

@section('content')
@php
    $perizinanAktif = (int) ($perizinanSummary['aktif'] ?? 0);
    $perizinanTerlambat = (int) ($perizinanSummary['terlambat'] ?? 0);
    $perizinanPending = (int) ($perizinanSummary['pending'] ?? 0);

    $heroTip = $perizinanTerlambat > 0
        ? 'Perhatian: '.$perizinanTerlambat.' perizinan anak terlambat kembali. Segera hubungi sekolah/pondok.'
        : ($perizinanAktif > 0
            ? $perizinanAktif.' anak sedang dalam perizinan aktif (belum kembali).'
            : ($stats['tagihan_belum_lunas'] > 0
                ? 'Terdapat '.$stats['tagihan_belum_lunas'].' tagihan anak yang belum lunas.'
                : ($stats['jumlah_anak'] > 0
                    ? 'Semua tagihan anak sudah lunas. Anak Anda hadir '.$stats['hadir_bulan_ini'].' kali bulan ini.'
                    : 'Hubungkan data anak melalui admin sekolah jika belum muncul.')));

    $heroChips = [
        ['label' => 'Jumlah Anak', 'value' => $stats['jumlah_anak']],
        ['label' => 'Tagihan Aktif', 'value' => $stats['tagihan_belum_lunas']],
        ['label' => 'Kehadiran Bulan Ini', 'value' => $stats['hadir_bulan_ini'].' hari'],
    ];

    if ($children->isNotEmpty()) {
        $heroChips[] = ['label' => 'Sedang Izin', 'value' => $perizinanAktif];
        if ($perizinanTerlambat > 0) {
            $heroChips[] = ['label' => 'Terlambat Kembali', 'value' => $perizinanTerlambat];
        }
        if ($perizinanPending > 0) {
            $heroChips[] = ['label' => 'Menunggu Izin', 'value' => $perizinanPending];
        }
    }

    $heroChips[] = ['label' => 'Saldo Keuangan', 'value' => 'Rp '.number_format($stats['saldo_keuangan'], 0, ',', '.')];
    $heroChips[] = ['label' => 'Pembayaran', 'value' => 'Rp '.number_format($stats['pembayaran_bulan_ini'], 0, ',', '.')];
@endphp
<div class="portal-dashboard dashboard-page">
    <x-portal.dashboard-hero
        :name="$greetingName"
        subtitle="Portal Orang Tua — pantau perkembangan anak Anda"
        :tip="$heroTip"
        highlight-label="Saldo Keuangan"
        :highlight-value="'Rp '.number_format($stats['saldo_keuangan'], 0, ',', '.')"
        tone="green"
        :chips="$heroChips"
    />

    @if($children->isEmpty())
        <div class="card portal-dashboard-panel p-6 text-center text-muted">
            <x-icon name="users-group" size="lg" class="mx-auto mb-3 opacity-40" />
            <p>Belum ada data anak yang terhubung ke akun Anda.</p>
        </div>
    @else
        <section class="portal-dashboard-section">
            <x-portal.panel-head
                title="Data Anak"
                subtitle="Ringkasan informasi anak yang terhubung ke akun wali."
                :action-url="route('portal.ortu.anak')"
                action-label="Kelola Anak"
                :bordered="false"
            />

            <div class="portal-dashboard-children">
                @foreach($children as $child)
                    @php
                        $profilePhotoUrl = $child->profil?->photoUrl();
                        $nameInitial = strtoupper(substr($child->name ?? '?', 0, 1));
                    @endphp
                    <div class="card portal-child-card">
                        <div class="portal-child-card__head">
                            <div class="portal-child-card__identity">
                                <div class="portal-child-card__avatar" aria-hidden="true">
                                    @if($profilePhotoUrl)
                                        <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $child->name }}" class="portal-child-card__avatar-image">
                                    @else
                                        <span>{{ $nameInitial }}</span>
                                    @endif
                                </div>
                                <div>
                                <p class="portal-child-card__name">{{ $child->name }}</p>
                                <p class="text-muted text-sm">NIS {{ $child->nis }}</p>
                                <div class="mt-2">
                                    <x-portal.vano
                                        compact
                                        :value="$child->virtualAccountNumber()"
                                        hint="Untuk transfer pembayaran tagihan"
                                    />
                                </div>
                                </div>
                            </div>
                            <span class="badge badge-green">{{ $child->statusLabel() }}</span>
                        </div>
                        <dl class="portal-child-card__meta">
                            <div>
                                <dt class="text-muted">Kelas</dt>
                                <dd>{{ $child->kelas?->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Saldo Keuangan</dt>
                                <dd>Rp {{ number_format($child->saldo_keuangan ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Saldo Cashless</dt>
                                <dd>Rp {{ number_format($child->saldo_cashless ?? 0, 0, ',', '.') }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="portal-dashboard-section mt-6">
            <x-perizinan.rekap-card
                :summary="$perizinanSummary"
                title="Rekap Perizinan Anak"
                :rekap-url="route('portal.ortu.rekap-perizinan.index')"
                :show-link="true"
            />
        </section>
    @endif

    <x-portal.quick-actions
        title="Akses Cepat"
        description="Pantau tagihan, absensi, perizinan, dan dompet anak."
        :actions="[
            ['label' => 'Data Anak', 'route' => 'portal.ortu.anak', 'icon' => 'users-group', 'tone' => 'primary'],
            ['label' => 'Rekap Perizinan', 'route' => 'portal.ortu.rekap-perizinan.index', 'icon' => 'calendar-event', 'tone' => 'accent'],
            ['label' => 'Tagihan', 'route' => 'portal.ortu.tagihan.index', 'icon' => 'receipt-2', 'tone' => 'accent'],
            ['label' => 'Pindah Saldo', 'route' => 'portal.ortu.pindah-saldo', 'icon' => 'arrows-exchange', 'tone' => 'green'],
            ['label' => 'Cashless', 'route' => 'portal.ortu.dompet.index', 'icon' => 'wallet', 'tone' => 'blue'],
            ['label' => 'PIN Cashless', 'route' => 'portal.ortu.pin-cashless.index', 'icon' => 'password', 'tone' => 'purple'],
        ]"
    />

    @if($children->isNotEmpty())
        <x-portal.recent-tagihan-table :tagihan="$recentTagihan" :show-student="true">
            <x-portal.panel-head
                title="Tagihan Anak Terbaru"
                subtitle="Tagihan SPP dari seluruh anak yang terhubung"
                :action-url="route('portal.ortu.tagihan.index')"
                action-label="Lihat Semua"
            />
        </x-portal.recent-tagihan-table>
    @endif
</div>
@endsection
