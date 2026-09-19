@props([
    'siswa',
    'siswaPortalTokenActive' => false,
    'ortuPortalTokenActive' => false,
    'hasOrangTua' => null,
])

@php
    $hasOrangTua = $hasOrangTua ?? $siswa->relationLoaded('orangTua')
        ? $siswa->orangTua->isNotEmpty()
        : $siswa->orangTua()->exists();
@endphp

<div class="flex flex-wrap items-center gap-2" data-portal-actions-context="page">
    @if($hasOrangTua)
        <x-portal-access-button-group
            label="Ortu"
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
        label="Siswa"
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
</div>
