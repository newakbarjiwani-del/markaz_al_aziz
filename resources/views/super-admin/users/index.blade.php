@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Daftar User Login"
    :ajax-url="!empty($canCreateUsers) ? route('super-admin.users.data') : route('admin.manajemen-user.data')"
    :columns="['Username', 'Nama', 'Email', 'Role', 'Sekolah', 'Status', 'Aksi']"
    :show-export="false"
    :column-options="[6 => ['html' => true]]">
    @if(!empty($canCreateUsers))
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="user-modal"
                    data-form-reset="user-form"
                    data-store-url="{{ route('super-admin.users.store') }}"
                    data-modal-title="Tambah User">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah User
            </button>
        </x-slot:actions>
    @endif
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        <div>
            <label class="form-label" for="filter-role">Role</label>
            <select name="role" id="filter-role" class="form-input">
                <option value="">Semua</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}">{{ str_replace('_', ' ', ucfirst($role)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-sekolah">Sekolah</label>
            <select name="sekolah_id" id="filter-sekolah" class="form-input" data-s2 data-placeholder="Semua Sekolah" data-allow-clear="0">
                <option></option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-user-status">Status</label>
            <select name="status" id="filter-user-status" class="form-input">
                <option value="">Semua</option>
                <option value="{{ \App\Support\UserStatus::ACTIVE }}">Aktif</option>
                <option value="{{ \App\Support\UserStatus::DISABLED }}">Nonaktif</option>
            </select>
        </div>
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@if(!empty($canManageUsers))
@push('modals')
<x-modal id="user-modal" title="Tambah / Ubah User">
    <form id="user-form"
          data-fetch-form
          data-reload-table
          data-close-modal="user-modal"
          data-default-action="{{ !empty($canCreateUsers) ? route('super-admin.users.store') : '' }}"
          action="{{ !empty($canCreateUsers) ? route('super-admin.users.store') : '' }}"
          method="POST"
          class="space-y-5">
        @csrf

        <p id="user-self-edit-note" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
            Anda mengedit akun sendiri. Peran dan status tidak dapat diubah. Masukkan password saat ini untuk menyimpan perubahan.
        </p>

        <section class="form-section">
            <h4 class="form-section__title">Informasi Akun</h4>
            <div class="form-section__body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="user-username">Username</label>
                        <input type="text" name="username" id="user-username" class="form-input" required autocomplete="username">
                    </div>
                    <div>
                        <label class="form-label" for="user-email">Email <span class="text-muted text-xs font-normal">(opsional)</span></label>
                        <input type="email" name="email" id="user-email" class="form-input" placeholder="Kosongkan jika tidak ada" autocomplete="email">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="user-name">Nama Lengkap</label>
                    <input type="text" name="name" id="user-name" class="form-input" required autocomplete="name">
                </div>
                <div>
                    <label class="form-label" for="user-phone">Telepon <span class="text-muted text-xs font-normal">(opsional)</span></label>
                    <input type="text" name="phone" id="user-phone" class="form-input" autocomplete="tel" placeholder="08xxxxxxxxxx">
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Pengaturan Keamanan</h4>
            <div class="form-section__body space-y-4">
                <div id="user-password-change-fields" class="space-y-4">
                    <x-form.password-input
                        name="password"
                        id="user-password"
                        label="Password"
                    />
                    <p id="user-password-hint" class="text-muted -mt-2 text-xs">Wajib untuk user baru. Kosongkan saat edit jika tidak diubah.</p>
                    <div id="user-password-confirm-field" class="hidden">
                        <x-form.password-input
                            name="password_confirmation"
                            id="user-password-confirmation"
                            label="Konfirmasi Password"
                        />
                    </div>
                </div>
                <p id="user-password-locked-note" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                    Password akun super admin lain tidak dapat diubah dari halaman ini.
                </p>
                <div id="user-self-confirm-panel" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                    <x-form.password-input
                        name="current_password"
                        id="user-current-password"
                        label="Password Saat Ini"
                        autocomplete="current-password"
                        placeholder="Wajib untuk menyimpan perubahan akun Anda"
                    />
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Peran &amp; Akses</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label">Role</label>

                    {{-- Hidden input for single-role submission (populated by JS when radio selected) --}}
                    <input type="hidden" name="role[]" id="user-role-single-value" value="">

                    @php
                        $singleRoleNames = ['super_admin', 'orang_tua', 'siswa'];
                        $multiRoleNames = $roles->reject(fn($r) => in_array($r, $singleRoleNames))->values();
                    @endphp

                    {{-- Single Role (radio) --}}
                    <div class="mb-3">
                        <p class="mb-2 text-xs font-medium text-muted">Single Role <span class="font-normal">(peran tunggal)</span></p>
                        <div class="flex flex-wrap gap-4">
                            @foreach($singleRoleNames as $roleName)
                                @if(in_array($roleName, $roles->toArray()))
                                <label class="inline-flex cursor-pointer items-center gap-2">
                                    <input type="radio" name="user-role-single" value="{{ $roleName }}" class="form-radio">
                                    <span class="text-sm">{{ $roleLabels[$roleName] ?? str_replace('_', ' ', ucfirst($roleName)) }}</span>
                                </label>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Multiple Role (Select2 multi) --}}
                    <div>
                        <p class="mb-2 text-xs font-medium text-muted">Multiple Role <span class="font-normal">(bisa digabung dengan peran lain)</span></p>
                        <select id="user-role-multi" name="role[]" class="form-input" data-s2 multiple
                                data-placeholder="Pilih satu atau lebih..."
                                data-allow-clear="0">
                            @foreach($multiRoleNames as $roleName)
                                <option value="{{ $roleName }}">{{ $roleLabels[$roleName] ?? str_replace('_', ' ', ucfirst($roleName)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="user-status">Status</label>
                    <select name="status" id="user-status" class="form-input">
                        <option value="{{ \App\Support\UserStatus::ACTIVE }}">Aktif</option>
                        <option value="{{ \App\Support\UserStatus::DISABLED }}">Nonaktif</option>
                    </select>
                </div>
                <div id="user-sekolah-field">
                    <label class="form-label" for="user-sekolah-id">Sekolah <span class="text-muted text-xs font-normal">(opsional)</span></label>
                    <select name="sekolah_id" id="user-sekolah-id" class="form-input" data-s2
                            data-placeholder="Semua sekolah (opsional)">
                        <option value=""></option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                    <p id="user-sekolah-hint" class="text-muted mt-1 text-xs">Kosongkan untuk akses semua sekolah.</p>
                </div>
                @if(!empty($canCreateUsers) && !empty($adminModules))
                <div id="user-modules-field" class="hidden">
                    <label class="form-label">Akses modul tambahan</label>
                    <p class="text-muted mb-2 text-xs">
                        Beri akses menu admin tanpa menambahkan peran operator. Modul yang sudah termasuk peran ditandai <em>dari peran</em>.
                    </p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($adminModules as $module)
                            <label class="inline-flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                                <input
                                    type="checkbox"
                                    name="modules[]"
                                    value="{{ $module['key'] }}"
                                    class="form-checkbox mt-0.5 user-module-checkbox"
                                    data-module-key="{{ $module['key'] }}"
                                >
                                <span class="text-sm leading-snug">
                                    {{ $module['label'] }}
                                    <span class="user-module-implied-badge ml-1 hidden text-xs font-normal text-muted">(dari peran)</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </section>

        <section id="user-portal-link-panel" class="form-section hidden">
            <h4 class="form-section__title">Keterkaitan Portal</h4>
            <p class="form-section__desc">Satu akun login hanya boleh terhubung ke satu entitas siswa, guru, atau orang tua.</p>
            <div class="form-section__body space-y-4">
                <p id="user-linked-entity-note" class="hidden rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs text-primary-900 dark:border-primary-900/50 dark:bg-primary-950/40 dark:text-primary-100"></p>
                <div id="user-portal-auto-hint" class="hidden alert alert-info">
                    <x-icon name="info-circle" size="md" class="shrink-0" />
                    <p>
                        Akun login dan token portal untuk <strong>siswa</strong> serta <strong>orang tua</strong> dapat dibuat otomatis dari halaman
                        <a href="{{ route('admin.manajemen-siswa.data-siswa.index') }}" class="font-medium underline underline-offset-2">Data Siswa</a>
                        atau
                        <a href="{{ route('admin.manajemen-siswa.orang-tua.index') }}" class="font-medium underline underline-offset-2">Data Orang Tua</a>
                        (tombol <em>Buat Token</em> pada baris aksi).
                        Gunakan form ini hanya jika perlu mengelola akun secara manual.
                    </p>
                </div>
                <div id="user-siswa-field" class="hidden">
                    <x-siswa-select :status="null" label="Siswa" :required="false" />
                </div>
                <div id="user-guru-field" class="hidden">
                    <x-guru-select :status="null" label="Guru" :required="false" />
                </div>
                <div id="user-ortu-field" class="hidden">
                    <x-orang-tua-select :status="null" label="Orang Tua" :required="false" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" data-modal-close="user-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
@endif

@push('scripts')
@if(!empty($canManageUsers))
<script src="{{ asset('js/user-form.js') }}?v=14"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('user-modal');
    window.initAjaxSelects?.(modal);
    window.initOfflineSelect2s?.(modal);
});
</script>
@endif
@endpush
