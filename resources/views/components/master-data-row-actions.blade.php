@props([
    'locked' => false,
    'lockedReason' => null,
    'partialEdit' => false,
    'editableFields' => 'default_amount,is_spp',
    'fields' => [],
    'editUrl' => '',
    'deleteUrl' => '',
    'deleteDetail' => null,
    'formTarget' => '',
    'modalTarget' => '',
])

<div class="action-group">
    @if(empty($locked))
        <button type="button"
                data-edit-record="{{ \App\Support\EditRecordPayload::encode($fields) }}"
                data-update-url="{{ $editUrl }}"
                data-form-target="{{ $formTarget }}"
                data-modal-target="{{ $modalTarget }}"
                class="btn-action btn-action-edit"
                title="Edit">
            <x-icon name="pencil" size="sm" />
            <span class="btn-action-label">Edit</span>
        </button>
        <button type="button"
                data-fetch-delete="{{ $deleteUrl }}"
                data-confirm-title="Konfirmasi Hapus"
                data-confirm-message="Data ini akan dihapus dari daftar aktif."
                @if(!empty($deleteDetail)) data-confirm-detail="{{ $deleteDetail }}" @endif
                data-confirm-text="Ya, Hapus"
                data-confirm-icon="ti-trash"
                class="btn-action btn-action-delete"
                title="Hapus">
            <x-icon name="trash" size="sm" />
            <span class="btn-action-label">Hapus</span>
        </button>
    @elseif($partialEdit)
        <button type="button"
                data-edit-record="{{ \App\Support\EditRecordPayload::encode($fields) }}"
                data-update-url="{{ $editUrl }}"
                data-form-target="{{ $formTarget }}"
                data-modal-target="{{ $modalTarget }}"
                data-partial-edit="1"
                data-editable-fields="{{ $editableFields }}"
                class="btn-action btn-action-edit"
                title="Ubah nominal default / SPP">
            <x-icon name="pencil" size="sm" />
            <span class="btn-action-label">Edit</span>
        </button>
        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300"
              title="{{ $lockedReason ?? 'Data sedang digunakan' }}">
            <x-icon name="lock" size="xs" /> Terpakai
        </span>
    @else
        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300"
              title="{{ $lockedReason ?? 'Data sedang digunakan' }}">
            <x-icon name="lock" size="xs" /> Terpakai
        </span>
    @endif
</div>
