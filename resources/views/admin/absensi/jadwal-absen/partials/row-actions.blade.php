<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.absensi.jadwal-absen.edit', $jadwal) }}"
       class="btn-action btn-action-edit"
       title="Edit">
        <x-icon name="pencil" size="sm" />
        <span class="btn-action-label">Edit</span>
    </a>
    @php
        $deleteConfirmDetail = [
            ['label' => 'Jadwal', 'value' => $jadwal->name],
        ];
    @endphp
    <button type="button"
            class="btn-action btn-action-delete"
            data-fetch-delete="{{ route('admin.absensi.jadwal-absen.destroy', $jadwal) }}"
            data-confirm-title="Hapus Jadwal Absen"
            data-confirm-message="Jadwal absen ini akan dihapus."
            data-confirm-detail="{{ \App\Support\ConfirmDetail::attr($deleteConfirmDetail) }}"
            data-confirm-text="Ya, Hapus"
            data-confirm-icon="ti-trash"
            title="Hapus">
        <x-icon name="trash" size="sm" />
        <span class="btn-action-label">Hapus</span>
    </button>
</div>
