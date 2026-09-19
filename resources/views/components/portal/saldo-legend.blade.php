@props(['highlight' => null])

@php
    $items = [
        'keuangan' => [
            'icon' => 'receipt-2',
            'label' => 'Saldo Keuangan',
            'desc' => 'Untuk membayar tagihan / SPP sekolah.',
        ],
        'cashless' => [
            'icon' => 'wallet',
            'label' => 'Saldo Cashless',
            'desc' => 'Uang saku untuk jajan di kantin (cashless).',
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'portal-saldo-legend']) }}>
    <div class="portal-saldo-legend__title">
        <span class="portal-saldo-legend__title-icon">
            <x-icon name="info-circle" size="sm" />
        </span>
        <span class="portal-saldo-legend__title-text">
            Dua saldo ini <strong>terpisah</strong> dan tidak otomatis tergabung.
        </span>
    </div>
    <div class="portal-saldo-legend__grid {{ $highlight ? 'portal-saldo-legend__grid--has-active' : '' }}">
        @foreach($items as $key => $item)
            @php $isActive = $highlight === $key; @endphp
            <div class="portal-saldo-legend__item {{ $isActive ? 'portal-saldo-legend__item--active' : '' }}">
                <span class="portal-saldo-legend__icon">
                    <x-icon :name="$item['icon']" size="sm" />
                </span>
                <div class="min-w-0">
                    <p class="portal-saldo-legend__label">{{ $item['label'] }}</p>
                    <p class="portal-saldo-legend__desc">{{ $item['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
    <p class="portal-saldo-legend__note">
        Untuk memindahkan dana dari Saldo Keuangan ke Saldo Cashless, gunakan menu
        <a href="{{ route('portal.ortu.pindah-saldo') }}" class="portal-saldo-legend__link">Pindah Saldo</a>.
    </p>
</div>
