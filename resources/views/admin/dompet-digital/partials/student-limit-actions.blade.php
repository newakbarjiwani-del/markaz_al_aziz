@props(['siswa'])

<div class="action-group">
    <button type="button"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode([
                'daily_transaction_limit' => $siswa->daily_transaction_limit,
            ]) }}"
            data-update-url="{{ route('admin.dompet-digital.limit-kontrol.update-siswa', $siswa) }}"
            data-form-target="student-limit-form"
            data-modal-target="student-limit-modal"
            data-modal-title="Limit Harian — {{ $siswa->name }}"
            class="btn-action btn-action-edit"
            title="Ubah limit harian">
        <x-icon name="pencil" size="sm" />
        <span class="btn-action-label">Limit</span>
    </button>
</div>
