@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $extra = $profil?->extra_fields ?? [];
    $hasFotoWajah = $siswa->hasFotoWajah();
    $profilePhotoUrl = $profil?->photoUrl();
@endphp

<div class="portal-profile">
    <section class="card portal-profile__summary">
        <div class="portal-profile__section-head">
            <h2 class="portal-profile__section-title">Data Siswa</h2>
            <p class="portal-profile__section-desc">Ringkasan identitas siswa</p>
        </div>
        <div class="portal-profile__identity">
            <p class="portal-profile__eyebrow">Profil Siswa</p>
            <h1 class="portal-profile__name">{{ $siswa->name }}</h1>

            <div class="portal-profile__chips">
                <span class="portal-profile__chip">
                    <x-icon name="id" size="xs" />
                    NIS {{ $siswa->nis }}
                </span>
                <span class="portal-profile__chip">
                    <x-icon name="school" size="xs" />
                    {{ $siswa->kelas?->name ?? 'Belum ada kelas' }}
                </span>
                <span class="portal-profile__chip portal-profile__chip--status">
                    <x-icon name="circle-check" size="xs" />
                    {{ ucfirst($siswa->status) }}
                </span>
            </div>
        </div>
    </section>

    <section class="card portal-profile__hero">
        <div class="portal-profile__section-head">
            <h2 class="portal-profile__section-title">Foto Profil vs Rekam Wajah</h2>
            <p class="portal-profile__section-desc">Keduanya terpisah dan dipakai untuk kebutuhan yang berbeda.</p>
        </div>
        <div class="portal-profile__legend">
            <div class="portal-profile__legend-item">
                <x-icon name="id-badge-2" size="sm" />
                <div>
                    <p class="portal-profile__legend-label">Foto Profil</p>
                    <p class="portal-profile__legend-text">Untuk kartu pelajar dan identitas visual akun.</p>
                </div>
            </div>
            <div class="portal-profile__legend-item">
                <x-icon name="camera" size="sm" />
                <div>
                    <p class="portal-profile__legend-label">Rekam Wajah</p>
                    <p class="portal-profile__legend-text">Untuk absensi dan verifikasi biometrik.</p>
                </div>
            </div>
        </div>

        <div class="portal-profile__media-group">
            <div class="portal-profile__media portal-profile__media-card">
                <p class="portal-profile__media-label">Foto Profil (Kartu Pelajar)</p>
                <div class="portal-profile__photo">
                    @if($profilePhotoUrl)
                        <img id="profile-photo-img"
                             src="{{ $profilePhotoUrl }}"
                             alt="Foto profil {{ $siswa->name }}"
                             class="portal-profile__photo-img">
                    @else
                        <img id="profile-photo-img"
                             src=""
                             alt="Foto profil {{ $siswa->name }}"
                             class="portal-profile__photo-img hidden">
                        <div id="profile-photo-placeholder" class="portal-profile__photo-placeholder">
                            <x-icon name="user" size="lg" />
                        </div>
                    @endif
                </div>
                <form id="profile-photo-form" data-fetch-form action="{{ route('portal.siswa.profil.photo') }}" method="POST" enctype="multipart/form-data" class="portal-profile__upload-form">
                    @csrf
                    <div class="portal-profile__upload-row">
                        <input type="file"
                               name="photo"
                               class="form-input"
                               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                               required>
                        <button type="submit" class="btn-secondary btn-sm">
                            <x-icon name="upload" size="sm" />
                            <span>Upload</span>
                        </button>
                    </div>
                </form>
                <p class="portal-profile__media-hint">JPEG/PNG/WebP, max 2 MB, min 200x200 px. Dipakai untuk kartu pelajar dan identitas visual.</p>
            </div>

            <div class="portal-profile__media portal-profile__media-card">
                <p class="portal-profile__media-label">Rekam Wajah (Absensi)</p>
                <div class="portal-profile__photo">
                    <img id="face-profile-photo"
                         src="{{ $hasFotoWajah ? route('portal.siswa.profil.foto-wajah').'?v='.($siswa->updated_at?->timestamp ?? $siswa->id) : '' }}"
                         alt="Foto referensi absensi {{ $siswa->name }}"
                         class="portal-profile__photo-img {{ $hasFotoWajah ? '' : 'hidden' }}">
                    <div id="face-profile-placeholder" class="portal-profile__photo-placeholder {{ $hasFotoWajah ? 'hidden' : '' }}">
                        <x-icon name="user" size="lg" />
                    </div>
                    <span id="face-profile-status" class="portal-profile__photo-badge face-status-badge {{ $hasFotoWajah ? 'face-status-badge--done' : 'face-status-badge--pending' }}">
                        <x-icon name="{{ $hasFotoWajah ? 'circle-check' : 'camera-off' }}" size="xs" />
                        <span data-face-status-text>{{ $hasFotoWajah ? 'Sudah direkam' : 'Belum direkam' }}</span>
                    </span>
                </div>
                <button type="button" class="btn-secondary btn-sm portal-profile__record-btn" data-open-face-capture-portal>
                    <x-icon name="camera" size="sm" />
                    <span>Rekam Wajah</span>
                </button>
                <p class="portal-profile__media-hint">Foto referensi untuk absensi. Berbeda dari foto profil di kartu pelajar.</p>
            </div>
        </div>

        <p class="portal-profile__note px-4 pb-4 sm:px-5">
            Pastikan kedua foto terisi agar kartu pelajar dan proses absensi berjalan optimal.
        </p>
    </section>

    <section class="card portal-profile__details">
        <div class="portal-profile__section-head">
            <h2 class="portal-profile__section-title">Informasi Pribadi</h2>
            <p class="portal-profile__section-desc">Data identitas dan kontak siswa</p>
        </div>

        <dl class="portal-profile__grid">
            <div class="portal-profile__item">
                <span class="portal-profile__item-icon" aria-hidden="true">
                    <x-icon name="users" size="sm" />
                </span>
                <div>
                    <dt>Jenis Kelamin</dt>
                    <dd>{{ $siswa->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</dd>
                </div>
            </div>
            <div class="portal-profile__item">
                <span class="portal-profile__item-icon" aria-hidden="true">
                    <x-icon name="calendar" size="sm" />
                </span>
                <div>
                    <dt>Tanggal Lahir</dt>
                    <dd>{{ $siswa->birth_date?->format('d/m/Y') ?? '-' }}</dd>
                </div>
            </div>
            <div class="portal-profile__item">
                <span class="portal-profile__item-icon" aria-hidden="true">
                    <x-icon name="map-pin" size="sm" />
                </span>
                <div>
                    <dt>Tempat Lahir</dt>
                    <dd>{{ $siswa->birth_place ?? '-' }}</dd>
                </div>
            </div>
            <div class="portal-profile__item">
                <span class="portal-profile__item-icon" aria-hidden="true">
                    <x-icon name="tag" size="sm" />
                </span>
                <div>
                    <dt>Nama Panggilan</dt>
                    <dd>{{ $extra['nama_panggilan'] ?? '-' }}</dd>
                </div>
            </div>
            <div class="portal-profile__item">
                <span class="portal-profile__item-icon" aria-hidden="true">
                    <x-icon name="droplet" size="sm" />
                </span>
                <div>
                    <dt>Golongan Darah</dt>
                    <dd>{{ $extra['golongan_darah'] ?? '-' }}</dd>
                </div>
            </div>
            <div class="portal-profile__item portal-profile__item--wide">
                <span class="portal-profile__item-icon" aria-hidden="true">
                    <x-icon name="home" size="sm" />
                </span>
                <div>
                    <dt>Alamat</dt>
                    <dd>{{ $siswa->address ?? '-' }}</dd>
                </div>
            </div>
        </dl>
    </section>
</div>
@endsection

@push('modals')
<x-face-capture-modal />
@endpush

@push('scripts')
<script src="{{ asset('js/face-capture.js') }}?v=4"></script>
<script>
window.initFaceCapture({
    csrfToken: @json(csrf_token()),
    storeUrl: @json(route('portal.siswa.profil.rekam-wajah.store')),
    deleteUrl: @json(route('portal.siswa.profil.rekam-wajah.destroy')),
    fotoUrl: @json(route('portal.siswa.profil.foto-wajah')),
    portalOpenSelector: '[data-open-face-capture-portal]',
    profilePhotoSelector: '#face-profile-photo',
    profilePlaceholderSelector: '#face-profile-placeholder',
    profileStatusSelector: '#face-profile-status',
    student: {
        id: @json($siswa->id),
        name: @json($siswa->name),
        nis: @json($siswa->nis),
        hasFoto: @json($hasFotoWajah),
    },
});

(function () {
    var form = document.getElementById('profile-photo-form');
    if (!form) return;

    form.addEventListener('fetch-success', function (event) {
        var photoUrl = event?.detail?.data?.photo_url;
        if (!photoUrl) return;

        var img = document.getElementById('profile-photo-img');
        var placeholder = document.getElementById('profile-photo-placeholder');
        if (!img) return;

        img.src = photoUrl + (photoUrl.indexOf('?') === -1 ? '?v=' : '&v=') + Date.now();
        img.classList.remove('hidden');
        placeholder?.classList.add('hidden');
    });
})();
</script>
@endpush
