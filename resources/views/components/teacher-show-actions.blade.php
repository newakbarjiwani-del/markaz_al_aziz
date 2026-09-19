@props([
    'guru',
    'hasAccount' => false,
])

@php
    $editFields = array_merge(
        $guru->only(['nip', 'name', 'jabatan', 'jenis_guru', 'golongan', 'phone', 'status', 'sekolah_id']),
        [
            'rfid_uid' => $guru->rfidUid(),
            'photo_url' => $guru->profil?->photoUrl(),
        ]
    );
@endphp

<div class="action-group action-group--wrap">
    <button type="button"
            class="btn-secondary btn-sm"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode($editFields) }}"
            data-update-url="{{ route('admin.manajemen-guru.data-guru.update', $guru) }}"
            data-form-target="teacher-form"
            data-modal-target="teacher-modal"
            data-modal-title="Ubah Guru"
            title="Edit guru">
        <x-icon name="pencil" size="sm" class="mr-1" />
        Edit
    </button>

    <button type="button"
            class="btn-secondary btn-sm {{ $hasAccount ? '' : 'btn-action-account-missing' }}"
            data-guru-account-trigger
            data-guru-name="{{ $guru->name }}"
            data-guru-nip="{{ $guru->nip }}"
            data-has-account="{{ $hasAccount ? '1' : '0' }}"
            data-account-url="{{ route('admin.manajemen-guru.data-guru.create-account', $guru) }}"
            data-account-show-url="{{ route('admin.manajemen-guru.data-guru.account.show', $guru) }}"
            data-account-reset-url="{{ route('admin.manajemen-guru.data-guru.account.reset-password', $guru) }}"
            title="{{ $hasAccount ? 'Kelola akun login' : 'Buat akun login' }}">
        <x-icon :name="$hasAccount ? 'key' : 'user-plus'" size="sm" class="mr-1" />
        {{ $hasAccount ? 'Akun Login' : 'Buat Akun' }}
    </button>

    @php
        $deleteConfirmDetail = [
            ['label' => 'Nama', 'value' => $guru->name],
            ['label' => 'NIP', 'value' => $guru->nip],
        ];
    @endphp
    <button type="button"
            class="btn-danger btn-sm"
            data-fetch-delete="{{ route('admin.manajemen-guru.data-guru.destroy', $guru) }}"
            data-fetch-delete-redirect="{{ route('admin.manajemen-guru.data-guru.index') }}"
            data-confirm-title="Hapus Guru"
            data-confirm-message="Data guru akan dihapus dari daftar aktif."
            data-confirm-detail="{{ \App\Support\ConfirmDetail::attr($deleteConfirmDetail) }}"
            data-confirm-text="Ya, Hapus"
            data-confirm-icon="ti-trash"
            title="Hapus guru">
        <x-icon name="trash" size="sm" class="mr-1" />
        Hapus
    </button>
</div>
