@props([
    'orangTua',
    'showViewLink' => true,
    'portalTokenActive' => false,
])

<div class="action-group action-group--wrap">
    @if($showViewLink)
        <a href="{{ route('admin.manajemen-siswa.orang-tua.show', $orangTua) }}"
           class="btn-action btn-action-view"
           title="Detail orang tua">
            <x-icon name="eye" size="sm" />
            <span class="btn-action-label">Detail</span>
        </a>
    @endif

    <div data-portal-actions-context="page">
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
