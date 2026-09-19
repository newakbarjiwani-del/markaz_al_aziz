@props([
    'siswa',
    'hasFotoWajah' => null,
    'siswaPortalTokenActive' => false,
    'ortuPortalTokenActive' => false,
    'hasOrangTua' => null,
])

@php
    $hasFotoWajah = $hasFotoWajah ?? $siswa->hasFotoWajah();
    $editFields = array_merge(
        $siswa->only([
            'nis', 'name', 'kelas_id', 'kamar_id', 'status_santri_id', 'gender', 'address', 'status',
            'daily_transaction_limit',
        ]),
        [
            'birth_date' => $siswa->birth_date?->format('Y-m-d'),
            'rfid_uid' => $siswa->rfidUid(),
            'rfid_blocked' => $siswa->isRfidBlocked(),
            'photo_url' => $siswa->profil?->photoUrl(),
        ]
    );
@endphp

<div class="action-group action-group--wrap">
    <button type="button"
            class="btn-secondary btn-sm"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode($editFields) }}"
            data-update-url="{{ route('admin.manajemen-siswa.data-siswa.update', $siswa) }}"
            data-form-target="student-form"
            data-modal-target="student-modal"
            data-modal-title="Ubah Siswa"
            title="Edit siswa">
        <x-icon name="pencil" size="sm" class="mr-1" />
        Edit
    </button>

    <button type="button"
            class="btn-secondary btn-sm"
            data-open-face-capture
            data-siswa-id="{{ $siswa->id }}"
            data-siswa-nis="{{ $siswa->nis }}"
            data-siswa-name="{{ $siswa->name }}"
            data-has-foto="{{ $hasFotoWajah ? '1' : '0' }}"
            title="Rekam wajah untuk absensi">
        <x-icon name="camera" size="sm" class="mr-1" />
        Rekam Wajah
    </button>

    <button type="button"
            class="btn-secondary btn-sm"
            data-siswa-orang-tua
            data-siswa-id="{{ $siswa->id }}"
            data-siswa-orang-tua-url="{{ route('admin.manajemen-siswa.data-siswa.orang-tua.index', $siswa) }}"
            data-siswa-orang-tua-reload="page"
            title="Kelola orang tua / wali">
        <x-icon name="users" size="sm" class="mr-1" />
        Orang Tua
    </button>

    <x-student-portal-actions
        :siswa="$siswa"
        :siswa-portal-token-active="$siswaPortalTokenActive"
        :ortu-portal-token-active="$ortuPortalTokenActive"
        :has-orang-tua="$hasOrangTua"
    />

@php
    $deleteConfirmDetail = [
        ['label' => 'Nama', 'value' => $siswa->name],
        ['label' => 'NIS', 'value' => $siswa->nis],
    ];
@endphp
    <button type="button"
            class="btn-danger btn-sm"
            data-fetch-delete="{{ route('admin.manajemen-siswa.data-siswa.destroy', $siswa) }}"
            data-fetch-delete-redirect="{{ route('admin.manajemen-siswa.data-siswa.index') }}"
            data-confirm-title="Hapus Siswa"
            data-confirm-message="Data siswa akan dihapus dari daftar aktif."
            data-confirm-detail="{{ \App\Support\ConfirmDetail::attr($deleteConfirmDetail) }}"
            data-confirm-text="Ya, Hapus"
            data-confirm-icon="ti-trash"
            title="Hapus siswa">
        <x-icon name="trash" size="sm" class="mr-1" />
        Hapus
    </button>
</div>
