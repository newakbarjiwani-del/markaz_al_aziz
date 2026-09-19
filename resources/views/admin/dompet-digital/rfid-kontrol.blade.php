@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-stats mb-6">
    <x-stat-card label="Kartu Aktif" :value="$stats['aktif']" icon-name="credit-card" accent="green" hint="RFID terdaftar & siap transaksi" />
    <x-stat-card label="Diblokir" :value="$stats['diblokir']" icon-name="lock" accent="red" hint="Kartu dinonaktifkan sementara" />
    <x-stat-card label="Belum Kartu" :value="$stats['belum_kartu']" icon-name="credit-card-off" accent="amber" hint="RFID belum diisi" />
</div>

<div class="card mb-4 p-4">
    <p class="text-muted text-sm">
        Pengaturan limit belanja ada di
        <a href="{{ route('admin.dompet-digital.limit-kontrol') }}" class="font-medium text-primary-700 underline dark:text-primary-300">Limit &amp; Kontrol</a>.
        PIN cashless 4 digit dipakai hanya saat <strong>Tarik Saldo</strong> melebihi limit harian — tidak untuk transaksi kantin/RFID.
    </p>
</div>

<x-admin.datatable-page
    title="RFID Siswa"
    subtitle="Daftar status kartu RFID siswa. Ubah UID, blokir kartu, atau kelola PIN cashless per siswa."
    :ajax-url="route('admin.dompet-digital.rfid-kontrol.data')"
    :columns="['NIS', 'Nama', 'Kelas', 'RFID UID', 'Status Kartu', 'PIN', 'Aksi']"
    :column-options="[
        3 => ['html' => true],
        4 => ['html' => true],
        5 => ['html' => true],
        6 => ['html' => true],
    ]">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
    <div>
      <label class="form-label" for="filter-rfid-blocked">Status Kartu</label>
      <select name="rfid_blocked" id="filter-rfid-blocked" class="form-input">
        <option value="">Semua status</option>
        <option value="0">Aktif / belum kartu</option>
        <option value="1">Diblokir</option>
      </select>
    </div>
    <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>

<x-modal id="rfid-update-modal" title="Ubah RFID">
    <form id="rfid-update-form"
          data-fetch-form
          method="POST"
          action="#"
          class="space-y-4"
          data-close-modal="rfid-update-modal"
          data-reload-table>
        @csrf

        <div class="rfid-edit-siswa">
            <div class="rfid-edit-siswa__header">
                <div>
                    <p class="rfid-edit-siswa__name" data-rfid-detail="name">—</p>
                    <p class="rfid-edit-siswa__meta">
                        <span data-rfid-detail="nis">—</span>
                        <span class="rfid-edit-siswa__dot" aria-hidden="true">·</span>
                        <span data-rfid-detail="kelas">—</span>
                    </p>
                </div>
                <div class="rfid-edit-siswa__aside">
                    <span class="rfid-edit-siswa__badge" data-rfid-detail="card_status">—</span>
                    <a href="#"
                       id="rfid-edit-siswa-link"
                       class="rfid-edit-siswa__link"
                       target="_blank"
                       rel="noopener">
                        Detail siswa
                    </a>
                </div>
            </div>
        </div>

        <div>
            <label class="form-label" for="rfid-update-uid">RFID UID</label>
            <input type="text"
                   name="rfid_uid"
                   id="rfid-update-uid"
                   class="form-input font-mono"
                   maxlength="64"
                   required
                   autocomplete="off"
                   placeholder="Scan / ketik UID kartu RFID"
                   data-rfid-scan-field>
            <p class="text-muted mt-1 text-xs">
                Fokus ke field ini lalu scan kartu. Enter dari scanner tidak akan mengirim form.
            </p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-modal-close="rfid-update-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan RFID
            </button>
        </div>
    </form>
</x-modal>

<x-modal id="cashless-pin-modal" title="PIN Cashless Siswa">
    <form id="cashless-pin-form"
          data-fetch-form
          method="POST"
          action="#"
          class="space-y-4"
          data-close-modal="cashless-pin-modal"
          data-reload-table>
        @csrf
        @method('PUT')
        <p class="text-sm text-slate-600 dark:text-slate-300" id="cashless-pin-siswa-label">-</p>
        <p class="text-muted text-xs">
            PIN hanya wajib untuk Tarik Saldo yang melebihi limit harian. Transaksi kantin tetap mengikuti limit tanpa PIN.
        </p>
        <div id="cashless-pin-current-wrap" class="hidden">
            <label class="form-label" for="cashless-pin-current">PIN Saat Ini</label>
            <input type="password"
                   name="current_pin"
                   id="cashless-pin-current"
                   class="form-input font-mono tracking-widest"
                   inputmode="numeric"
                   maxlength="4"
                   autocomplete="off">
        </div>
        <div>
            <label class="form-label" for="cashless-pin-new">PIN Baru (4 digit)</label>
            <input type="password"
                   name="pin"
                   id="cashless-pin-new"
                   class="form-input font-mono tracking-widest"
                   inputmode="numeric"
                   maxlength="4"
                   autocomplete="new-password"
                   required>
        </div>
        <div>
            <label class="form-label" for="cashless-pin-confirm">Ulangi PIN Baru</label>
            <input type="password"
                   name="pin_confirmation"
                   id="cashless-pin-confirm"
                   class="form-input font-mono tracking-widest"
                   inputmode="numeric"
                   maxlength="4"
                   autocomplete="new-password"
                   required>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-modal-close="cashless-pin-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="key" size="sm" class="mr-1" /> Simpan PIN
            </button>
        </div>
    </form>
</x-modal>
@endsection

@push('scripts')
<script src="{{ asset('js/rfid-kontrol.js') }}?v=2"></script>
<script src="{{ asset('js/cashless-pin.js') }}?v=4"></script>
@endpush
