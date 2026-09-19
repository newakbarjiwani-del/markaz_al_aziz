<div class="action-group">
    <button type="button"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode($fields) }}"
            data-update-url="{{ $editUrl }}"
            data-form-target="{{ $formTarget }}"
            data-modal-target="{{ $modalTarget }}"
            @if(! empty($editSelf)) data-edit-self="1" @endif
            class="btn-action btn-action-edit"
            title="Edit">
        <x-icon name="pencil" size="sm" />
        <span class="btn-action-label">Edit</span>
    </button>
    @if(! empty($deleteUrl))
    <button type="button"
            data-fetch-delete="{{ $deleteUrl }}"
            class="btn-action btn-action-delete"
            title="Hapus">
        <x-icon name="trash" size="sm" />
        <span class="btn-action-label">Hapus</span>
    </button>
    @endif
</div>
