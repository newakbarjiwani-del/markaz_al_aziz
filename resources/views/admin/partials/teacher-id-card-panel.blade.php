@props([
    'guru',
    'kartu' => null,
    'logoUrl' => null,
])

@php
    $kartu = $kartu ?? $guru->kartuAktif;
    $logoUrl = $logoUrl ?? asset('logo.png');
    $photoUrl = $guru->profil?->photoUrl();
@endphp

<div class="id-card-set">
    <div class="id-card-set__header">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Kartu Guru</h2>
            @if($kartu)
                <p class="text-muted mt-1 text-sm">Status {{ ucfirst($kartu->status) }}</p>
            @else
                <p class="text-muted mt-1 text-sm">Belum ada kartu aktif terdaftar untuk guru ini.</p>
            @endif
        </div>
        <button type="button"
                class="btn-primary btn-sm shrink-0"
                data-id-card-print-target="#teacher-id-card-faces">
            <x-icon name="printer" size="sm" class="mr-1" /> Cetak Kartu
        </button>
    </div>

    <div id="teacher-id-card-faces" class="id-card-modal-faces id-card-show-page__faces">
        <div class="id-card-show-page__face">
            <p class="id-card-set__label">Depan</p>
            <div class="id-card-modal-face">
                <x-id-card.teacher-front :guru="$guru" :photo-url="$photoUrl" :logo-url="$logoUrl" />
            </div>
        </div>
        <div class="id-card-show-page__face">
            <p class="id-card-set__label">Belakang</p>
            <div class="id-card-modal-face">
                <x-id-card.teacher-back :nip="$guru->nip" :logo-url="$logoUrl" />
            </div>
        </div>
    </div>

    @unless($kartu)
        <p class="text-muted mt-4 text-xs">
            Pratinjau kartu tetap ditampilkan dari data guru. Pastikan foto profil sudah diunggah lewat <strong>Edit</strong> agar muncul di kartu.
        </p>
    @endunless
</div>
