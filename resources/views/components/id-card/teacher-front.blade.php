@props([
    'guru',
    'photoUrl' => null,
    'mataPelajaran' => null,
    'logoUrl' => null,
    'schoolName' => null,
    'schoolSubtitle' => null,
])

@php
    $logoUrl = $logoUrl ?? asset('logo.png');
    $schoolName = $schoolName ?? config('app.nama_instansi', 'YAYASAN ITTIHAD PEKANBARU');
    $schoolSubtitle = $schoolSubtitle ?? strtoupper((string) config('app.domisili', 'Pekanbaru'));
    $photoUrl = $photoUrl ?? $guru->profil?->photoUrl();
    $initials = collect(explode(' ', $guru->name ?? ''))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $mataPelajaran = $mataPelajaran ?? $guru->riwayatMengajar?->first()?->subject ?? '-';
    $tahunBergabung = ($guru->created_at?->format('Y') ?? date('Y')).'/'.(($guru->created_at?->year ?? date('Y')) + 1);
@endphp

<div class="id-card id-card--teacher id-card--front">
    <div class="id-card__green-panel">
        <img src="{{ $logoUrl }}" alt="Logo" class="id-card__logo">
        <div class="id-card__photo">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $guru->name }}">
            @else
                <span class="id-card__photo-placeholder">{{ $initials ?: '?' }}</span>
            @endif
        </div>
    </div>

    <div class="id-card__body">
        <div class="id-card__watermark" style="background-image: url('{{ $logoUrl }}')" aria-hidden="true"></div>
        <p class="id-card__school-line1">Yayasan</p>
        <p class="id-card__school-line2">{{ $schoolName }}</p>
        <p class="id-card__school-line3">{{ $schoolSubtitle }}</p>
        <p class="id-card__ornament">— ✦ —</p>

        <div class="id-card__badge">
            <span class="id-card__badge-dot"></span>
            Kartu Guru
            <span class="id-card__badge-dot"></span>
        </div>

        <dl class="id-card__fields">
            <div class="id-card__field">
                <i class="ti ti-user id-card__field-icon"></i>
                <span class="id-card__field-label">Nama</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ strtoupper($guru->name ?? '-') }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-id id-card__field-icon"></i>
                <span class="id-card__field-label">NIP</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ $guru->nip ?? '-' }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-briefcase id-card__field-icon"></i>
                <span class="id-card__field-label">Jabatan</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ strtoupper($guru->jabatan ?? '-') }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-book id-card__field-icon"></i>
                <span class="id-card__field-label">Mapel</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ $mataPelajaran }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-phone id-card__field-icon"></i>
                <span class="id-card__field-label">No. HP</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ $guru->phone ?? '-' }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-calendar-event id-card__field-icon"></i>
                <span class="id-card__field-label">Tahun</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ $tahunBergabung }}</span>
            </div>
        </dl>
    </div>

    <div class="id-card__footer">Ilmu — Iman — Amal — Akhlak — Dakwah</div>
</div>
