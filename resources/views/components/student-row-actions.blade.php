@props([
    'siswa',
    'hasFotoWajah' => false,
    'siswaPortalTokenActive' => false,
    'ortuPortalTokenActive' => false,
    'hasOrangTua' => false,
    'fields' => [],
    'editUrl' => '',
    'deleteUrl' => '',
    'formTarget' => '',
    'modalTarget' => '',
])

<div class="row-action-menu" data-row-menu>
    <a href="{{ route('admin.manajemen-siswa.data-siswa.show', $siswa) }}"
       class="btn-action btn-action-view"
       title="Detail siswa">
        <x-icon name="eye" size="sm" />
        <span class="btn-action-label">Detail</span>
    </a>

    <button type="button"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode($fields) }}"
            data-update-url="{{ $editUrl }}"
            data-form-target="{{ $formTarget }}"
            data-modal-target="{{ $modalTarget }}"
            data-modal-title="Ubah Siswa"
            class="btn-action btn-action-edit"
            title="Edit siswa">
        <x-icon name="pencil" size="sm" />
        <span class="btn-action-label">Edit</span>
    </button>

    <button type="button"
            class="btn-action btn-action-menu"
            data-row-menu-toggle
            aria-expanded="false"
            aria-haspopup="menu"
            title="Menu aksi">
        <x-icon name="dots-vertical" size="sm" />
        <span class="btn-action-label">Lainnya</span>
    </button>

    <div class="row-action-menu-panel" data-row-menu-panel role="menu" aria-label="Aksi siswa">
        <button type="button"
                class="row-action-menu-item"
                role="menuitem"
                data-open-face-capture
                data-siswa-id="{{ $siswa->id }}"
                data-siswa-nis="{{ $siswa->nis }}"
                data-siswa-name="{{ $siswa->name }}"
                data-has-foto="{{ $hasFotoWajah ? '1' : '0' }}">
            <x-icon name="camera" size="sm" />
            <span>Rekam wajah (absensi)</span>
        </button>

        <button type="button"
                class="row-action-menu-item"
                role="menuitem"
                data-siswa-orang-tua
                data-siswa-id="{{ $siswa->id }}"
                data-siswa-orang-tua-url="{{ route('admin.manajemen-siswa.data-siswa.orang-tua.index', $siswa) }}">
            <x-icon name="users" size="sm" />
            <span>Kelola orang tua</span>
        </button>

        @if($hasOrangTua)
            <div class="row-action-menu-divider" role="separator"></div>

            <x-portal-access-button-group
                label="Portal orang tua"
                layout="menu"
                :has-active-token="$ortuPortalTokenActive"
                :generate-url="route('admin.manajemen-siswa.data-siswa.portal-access.ortu.link', $siswa)"
                :wa-url="route('admin.manajemen-siswa.data-siswa.portal-access.ortu', $siswa)"
                :copy-url="route('admin.manajemen-siswa.data-siswa.portal-access.ortu.link.copy', $siswa)"
                :revoke-url="route('admin.manajemen-siswa.data-siswa.portal-access.revoke', $siswa)"
                revoke-role="orang_tua"
                wa-title="Kirim link portal orang tua via WhatsApp"
                generate-title="Buat token login portal orang tua"
            />
        @endif

        <x-portal-access-button-group
            label="Portal siswa"
            layout="menu"
            :has-active-token="$siswaPortalTokenActive"
            :generate-url="route('admin.manajemen-siswa.data-siswa.portal-access.siswa.link', $siswa)"
            :wa-url="route('admin.manajemen-siswa.data-siswa.portal-access.siswa', $siswa)"
            :copy-url="route('admin.manajemen-siswa.data-siswa.portal-access.siswa.link.copy', $siswa)"
            :revoke-url="route('admin.manajemen-siswa.data-siswa.portal-access.revoke', $siswa)"
            revoke-role="siswa"
            wa-icon="send"
            wa-title="Kirim link portal siswa via WhatsApp"
            generate-title="Buat token login portal siswa"
        />

        <div class="row-action-menu-divider" role="separator"></div>
        <button type="button"
                class="row-action-menu-item row-action-menu-item--danger"
                role="menuitem"
                data-fetch-delete="{{ $deleteUrl }}">
            <x-icon name="trash" size="sm" />
            <span>Hapus siswa</span>
        </button>
    </div>
</div>
