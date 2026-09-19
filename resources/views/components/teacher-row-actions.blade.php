@props([
    'guru',
    'hasAccount' => false,
    'fields' => [],
    'editUrl' => '',
    'deleteUrl' => '',
    'accountUrl' => '',
    'accountShowUrl' => '',
    'accountResetUrl' => '',
    'formTarget' => 'teacher-form',
    'modalTarget' => 'teacher-modal',
])

<div class="action-group">
    <a href="{{ route('admin.manajemen-guru.data-guru.show', $guru) }}"
       class="btn-action btn-action-view"
       title="Detail guru">
        <x-icon name="eye" size="sm" />
        <span class="btn-action-label">Detail</span>
    </a>
    <button type="button"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode($fields) }}"
            data-update-url="{{ $editUrl }}"
            data-form-target="{{ $formTarget }}"
            data-modal-target="{{ $modalTarget }}"
            data-modal-title="Ubah Guru"
            class="btn-action btn-action-edit"
            title="Edit">
        <x-icon name="pencil" size="sm" />
        <span class="btn-action-label">Edit</span>
    </button>
    <button type="button"
            class="btn-action {{ $hasAccount ? 'btn-action-account' : 'btn-action-account-missing' }}"
            data-guru-account-trigger
            data-guru-name="{{ $guru->name }}"
            data-guru-nip="{{ $guru->nip }}"
            data-has-account="{{ $hasAccount ? '1' : '0' }}"
            data-account-url="{{ $accountUrl }}"
            data-account-show-url="{{ $accountShowUrl }}"
            data-account-reset-url="{{ $accountResetUrl }}"
            title="{{ $hasAccount ? 'Kelola akun login' : 'Buat akun login' }}">
        <x-icon :name="$hasAccount ? 'key' : 'user-plus'" size="sm" />
        <span class="btn-action-label">{{ $hasAccount ? 'Akun' : 'Buat Akun' }}</span>
    </button>
    <button type="button"
            data-fetch-delete="{{ $deleteUrl }}"
            class="btn-action btn-action-delete"
            title="Hapus">
        <x-icon name="trash" size="sm" />
        <span class="btn-action-label">Hapus</span>
    </button>
</div>
