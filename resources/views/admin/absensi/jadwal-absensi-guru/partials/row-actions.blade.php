<div class="action-group">
    <button type="button"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode([
                'sekolah_id' => $jadwal->sekolah_id,
                'name' => $jadwal->name,
                'jam_masuk' => $jadwal->jamMasukInput(),
                'jam_pulang' => $jadwal->jamPulangInput(),
                'toleransi_menit' => $jadwal->toleransi_menit,
                'is_active' => $jadwal->is_active ? '1' : '0',
            ]) }}"
            data-update-url="{{ route('admin.absensi.jadwal-absensi-guru.update', $jadwal) }}"
            data-form-target="jadwal-absensi-guru-form"
            data-modal-target="jadwal-absensi-guru-modal"
            data-modal-title="Ubah Jadwal Absensi Guru"
            class="btn-action btn-action-edit"
            title="Edit">
        <x-icon name="pencil" size="sm" />
        <span class="btn-action-label">Edit</span>
    </button>
    <a href="{{ route('admin.absensi.jadwal-absensi-guru.absensi', $jadwal) }}"
       class="btn-action btn-action-view"
       title="Absensi Guru"
       target="_blank"
       rel="noopener">
        <x-icon name="calendar-check" size="sm" />
        <span class="btn-action-label">Absensi</span>
    </a>
    <button type="button"
            data-assign-jadwal-guru
            data-jadwal-id="{{ $jadwal->id }}"
            data-gurus-url="{{ route('admin.absensi.jadwal-absensi-guru.gurus', $jadwal) }}"
            data-assign-url="{{ route('admin.absensi.jadwal-absensi-guru.assign', $jadwal) }}"
            class="btn-action btn-action-view"
            title="Atur Guru">
        <x-icon name="users" size="sm" />
        <span class="btn-action-label">Guru</span>
    </button>
    <button type="button"
            data-fetch-delete="{{ route('admin.absensi.jadwal-absensi-guru.destroy', $jadwal) }}"
            class="btn-action btn-action-delete"
            title="Hapus">
        <x-icon name="trash" size="sm" />
        <span class="btn-action-label">Hapus</span>
    </button>
</div>
