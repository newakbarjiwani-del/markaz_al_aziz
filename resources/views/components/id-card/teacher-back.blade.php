@props([
    'nip' => null,
    'logoUrl' => null,
    'schoolName' => null,
    'schoolSubtitle' => null,
    'address' => null,
    'website' => null,
    'instagram' => null,
    'quote' => 'Mengajar adalah amanah. Ilmu yang bermanfaat adalah yang diamalkan dan disampaikan dengan akhlak mulia.',
])

@php
    $logoUrl = $logoUrl ?? asset('logo.png');
    $schoolName = $schoolName ?? config('app.nama_instansi', 'YAYASAN ITTIHAD PEKANBARU');
    $schoolSubtitle = $schoolSubtitle ?? strtoupper((string) config('app.domisili', 'Pekanbaru'));
    $address = $address ?? config('app.alamat', 'Pekanbaru, Riau, Indonesia');
    $website = $website ?? (config('app.website') ?: null);
    $instagram = $instagram ?? null;
@endphp

<div class="id-card id-card--teacher id-card--back">
    <div class="id-card__back-pattern" aria-hidden="true"></div>
    <div class="id-card__back-corner-tr" aria-hidden="true"></div>
    <div class="id-card__back-corner-bl" aria-hidden="true"></div>

    <div class="id-card__back-header">
        <img src="{{ $logoUrl }}" alt="Logo" class="id-card__logo">
        <div>
            <p class="id-card__school-line1">Yayasan</p>
            <p class="id-card__school-line2" style="font-size:14px">{{ $schoolName }}</p>
            <p class="id-card__school-line3">{{ $schoolSubtitle }}</p>
        </div>
        <div class="id-card__back-quote">
            <span class="id-card__back-quote-mark">"</span>
            {{ $quote }}
        </div>
    </div>

    <div class="id-card__back-divider"></div>

    <div class="id-card__back-content">
        <div>
            <div class="id-card__section-title">
                <i class="ti ti-file-text"></i> Ketentuan
            </div>
            <ol class="id-card__rules">
                <li>
                    <span class="id-card__rule-num">1</span>
                    <span>Kartu ini adalah identitas resmi guru/staf {{ $schoolName }}.</span>
                </li>
                <li>
                    <span class="id-card__rule-num">2</span>
                    <span>Wajib dibawa saat bertugas mengajar dan kegiatan resmi yayasan.</span>
                </li>
                <li>
                    <span class="id-card__rule-num">3</span>
                    <span>Kartu tidak dapat dipindahtangankan. Kehilangan segera laporkan ke bagian TU.</span>
                </li>
                <li>
                    <span class="id-card__rule-num">4</span>
                    <span>Pemegang kartu wajib menjaga nama baik dan etika sebagai pendidik.</span>
                </li>
                <li>
                    <span class="id-card__rule-num">5</span>
                    <span>Berlaku selama status kepegawaian aktif di {{ $schoolName }}.</span>
                </li>
            </ol>
        </div>

        <div class="id-card__back-vline"></div>

        <div>
            <div class="id-card__contacts">
                <div class="id-card__contact">
                    <div class="id-card__contact-icon"><i class="ti ti-map-pin"></i></div>
                    <span>{{ $address }}</span>
                </div>
                @if(filled($website))
                    <div class="id-card__contact">
                        <div class="id-card__contact-icon"><i class="ti ti-world"></i></div>
                        <span>{{ $website }}</span>
                    </div>
                @endif
                @if(filled($instagram))
                    <div class="id-card__contact">
                        <div class="id-card__contact-icon"><i class="ti ti-brand-instagram"></i></div>
                        <span>{{ $instagram }}</span>
                    </div>
                @endif
                @if(filled(config('app.telepon')))
                    <div class="id-card__contact">
                        <div class="id-card__contact-icon"><i class="ti ti-phone"></i></div>
                        <span>{{ config('app.telepon') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($nip)
        <div class="id-card__qr" title="NIP">
            <span class="id-card__qr-code">{{ $nip }}</span>
        </div>
    @endif

    <div class="id-card__back-footer">
        <span class="id-card__back-footer-ornament" aria-hidden="true">&#10022;</span>
        Ilmu — Iman — Amal — Akhlak — Dakwah
    </div>
</div>
