@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Riwayat Login"
    subtitle="Login via username/email (web & API) dan token portal. Klik Detail untuk informasi lengkap."
    :ajax-url="route('super-admin.login-logs.data')"
    :columns="['Waktu', 'Identitas', 'Metode', 'Status', 'IP', 'User', 'Aksi']"
    :default-order="[[0, 'desc']]"
    :show-export="false"
    :column-options="[6 => ['html' => true]]">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        <div>
            <label class="form-label" for="filter-method">Metode</label>
            <select name="method" id="filter-method" class="form-input">
                <option value="">Semua</option>
                @foreach($methods as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-status">Status</label>
            <select name="status" id="filter-status" class="form-input">
                <option value="">Semua</option>
                <option value="success">Berhasil</option>
                <option value="failed">Gagal</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-date-from">Dari Tanggal</label>
            <input type="date" name="date_from" id="filter-date-from" class="form-input">
        </div>
        <div>
            <label class="form-label" for="filter-date-to">Sampai Tanggal</label>
            <input type="date" name="date_to" id="filter-date-to" class="form-input">
        </div>
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>

<x-modal id="login-log-detail-modal" title="Detail Log Login">
    <div id="login-log-detail-root"
         class="login-log-detail"
         data-show-url="{{ url('/super-admin/login-logs') }}">
        <dl class="login-log-detail__list">
            <div><dt>Waktu</dt><dd data-field="created_at">-</dd></div>
            <div><dt>Status</dt><dd data-field="status">-</dd></div>
            <div><dt>Metode</dt><dd data-field="method_label">-</dd></div>
            <div><dt>Identitas</dt><dd data-field="identifier">-</dd></div>
            <div><dt>Username</dt><dd data-field="username">-</dd></div>
            <div><dt>Nama</dt><dd data-field="name">-</dd></div>
            <div><dt>Role</dt><dd data-field="role">-</dd></div>
            <div><dt>Alamat IP</dt><dd data-field="ip_address">-</dd></div>
            <div><dt>Browser</dt><dd data-field="browser">-</dd></div>
            <div><dt>Sistem Operasi</dt><dd data-field="platform">-</dd></div>
            <div><dt>Perangkat</dt><dd data-field="device">-</dd></div>
            <div class="login-log-detail__full"><dt>User Agent</dt><dd data-field="user_agent" class="login-log-detail__mono">-</dd></div>
            <div class="login-log-detail__full"><dt>Keterangan</dt><dd data-field="message">-</dd></div>
            <div><dt>Token Portal</dt><dd data-field="portal_access_token_id">-</dd></div>
        </dl>
    </div>
</x-modal>
@endsection

@push('scripts')
    <script src="{{ asset('js/login-log-detail.js') }}?v=1"></script>
@endpush
