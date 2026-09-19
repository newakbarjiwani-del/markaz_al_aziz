@props([
    'siswa',
    'photoUrl' => null,
    'tahunMasuk' => null,
    'logoUrl' => null,
    'schoolName' => null,
    'schoolSubtitle' => null,
])

@php
    use App\Support\DisplayDate;

    $logoUrl = $logoUrl ?? asset('logo.png');
    $schoolName = $schoolName ?? config('app.nama_instansi', 'YAYASAN ITTIHAD PEKANBARU');
    $schoolSubtitle = $schoolSubtitle ?? strtoupper((string) config('app.domisili', 'Pekanbaru'));
    $photoUrl = $photoUrl ?? $siswa->profil?->photoUrl();
    $initials = collect(explode(' ', $siswa->name ?? ''))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $birthPlace = trim((string) ($siswa->birth_place ?? ''));
    $birthDate = DisplayDate::date($siswa->birth_date, '');
    $ttl = match (true) {
        $birthPlace !== '' && $birthDate !== '' => "{$birthPlace}, {$birthDate}",
        $birthPlace !== '' => $birthPlace,
        $birthDate !== '' => $birthDate,
        default => '-',
    };
    $alamat = $siswa->address ?? '-';
    $kelas = $siswa->kelas?->name ?? '-';
    $tahun = $tahunMasuk ?? ($siswa->created_at?->format('Y') ?? date('Y')).'/'.(($siswa->created_at?->year ?? (int) date('Y')) + 1);
@endphp

<div class="id-card id-card--student id-card--front">
    <div class="id-card__green-panel">
        <img src="{{ $logoUrl }}" alt="Logo" class="id-card__logo">
        <div class="id-card__photo">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $siswa->name }}">
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
            Kartu Pelajar
            <span class="id-card__badge-dot"></span>
        </div>

        <dl class="id-card__fields">
            <div class="id-card__field">
                <i class="ti ti-user id-card__field-icon"></i>
                <span class="id-card__field-label">Nama</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ strtoupper($siswa->name ?? '-') }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-id id-card__field-icon"></i>
                <span class="id-card__field-label">NIS</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value font-mono">{{ $siswa->nis ?? '-' }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-school id-card__field-icon"></i>
                <span class="id-card__field-label">Kelas</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ strtoupper($kelas) }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-calendar id-card__field-icon"></i>
                <span class="id-card__field-label">TTL</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ $ttl }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-map-pin id-card__field-icon"></i>
                <span class="id-card__field-label">Alamat</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ strtoupper($alamat) }}</span>
            </div>
            <div class="id-card__field">
                <i class="ti ti-calendar-event id-card__field-icon"></i>
                <span class="id-card__field-label">Masuk</span>
                <span class="id-card__field-sep">:</span>
                <span class="id-card__field-value">{{ $tahun }}</span>
            </div>
        </dl>
    </div>

    <div class="id-card__footer">Ilmu — Iman — Amal — Akhlak — Dakwah</div>
</div>
