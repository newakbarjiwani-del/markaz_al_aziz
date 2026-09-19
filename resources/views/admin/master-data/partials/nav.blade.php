@php
    $current = $active ?? '';
@endphp
<nav class="master-data-nav card mb-4 p-2">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.master-data.sekolah.index') }}"
           class="master-data-nav__link {{ $current === 'sekolah' ? 'is-active' : '' }}">
            <x-icon name="building" size="sm" /> Sekolah
        </a>
        <a href="{{ route('admin.master-data.kelas.index') }}"
           class="master-data-nav__link {{ $current === 'kelas' ? 'is-active' : '' }}">
            <x-icon name="school" size="sm" /> Kelas
        </a>
        <a href="{{ route('admin.master-data.tahun-akademik.index') }}"
           class="master-data-nav__link {{ $current === 'tahun-akademik' ? 'is-active' : '' }}">
            <x-icon name="calendar" size="sm" /> Tahun Akademik
        </a>
        <a href="{{ route('admin.master-data.jenis-tagihan.index') }}"
           class="master-data-nav__link {{ $current === 'jenis-tagihan' ? 'is-active' : '' }}">
            <x-icon name="receipt" size="sm" /> Jenis Tagihan
        </a>
        <a href="{{ route('admin.master-data.kamar.index') }}"
           class="master-data-nav__link {{ $current === 'kamar' ? 'is-active' : '' }}">
            <x-icon name="bed" size="sm" /> Kamar
        </a>
        <a href="{{ route('admin.master-data.status-santri.index') }}"
           class="master-data-nav__link {{ $current === 'status-santri' ? 'is-active' : '' }}">
            <x-icon name="users" size="sm" /> Status Santri
        </a>
    </div>
</nav>
