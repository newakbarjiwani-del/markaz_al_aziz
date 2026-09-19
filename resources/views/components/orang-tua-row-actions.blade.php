@props([
    'orangTua',
    'fields' => [],
    'editUrl' => '',
    'deleteUrl' => '',
    'showUrl' => '',
    'formTarget' => 'orang-tua-form',
    'modalTarget' => 'orang-tua-modal',
    'portalTokenActive' => false,
])

<div class="flex items-center gap-1">
    <a href="{{ $showUrl }}"
       class="btn-action btn-action-view"
       title="Detail orang tua">
        <x-icon name="eye" size="sm" />
        <span class="btn-action-label">Detail</span>
    </a>

    <button type="button"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode($fields) }}"
            data-update-url="{{ $editUrl }}"
            data-form-target="{{ $formTarget }}"
            data-modal-target="{{ $modalTarget }}"
            data-modal-title="Ubah Orang Tua"
            class="btn-action btn-action-edit"
            title="Edit orang tua">
        <x-icon name="pencil" size="sm" />
        <span class="btn-action-label">Edit</span>
    </button>

    @php
        $deleteConfirmDetail = [
            ['label' => 'Nama', 'value' => $orangTua->displayName()],
        ];
    @endphp
    <button type="button"
            class="btn-action btn-action-delete"
            title="Hapus"
            data-fetch-delete="{{ $deleteUrl }}"
            data-confirm-title="Hapus Orang Tua"
            data-confirm-message="Data orang tua akan dihapus dari daftar aktif."
            data-confirm-detail="{{ \App\Support\ConfirmDetail::attr($deleteConfirmDetail) }}"
            data-confirm-text="Ya, Hapus"
            data-confirm-icon="ti-trash">
        <x-icon name="trash" size="sm" />
        <span class="btn-action-label">Hapus</span>
    </button>

    <div data-portal-actions-context="row">
        <x-portal-access-button-group
            label="Portal"
            :has-active-token="$portalTokenActive"
            :generate-url="route('admin.manajemen-siswa.orang-tua.portal-access.link', $orangTua)"
            :wa-url="route('admin.manajemen-siswa.orang-tua.portal-access.send', $orangTua)"
            :copy-url="route('admin.manajemen-siswa.orang-tua.portal-access.link.copy', $orangTua)"
            :revoke-url="route('admin.manajemen-siswa.orang-tua.portal-access.revoke', $orangTua)"
            wa-title="Kirim link portal orang tua via WhatsApp"
            generate-title="Buat token login portal orang tua"
        />
    </div>
</div>
