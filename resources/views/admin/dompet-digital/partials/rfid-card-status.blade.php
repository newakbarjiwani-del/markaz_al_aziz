@props(['siswa'])

@php
    $hasRfid = $siswa->hasRfid();

    if ($siswa->isRfidBlocked()) {
        $status = 'blocked';
        $label = 'Diblokir';
        $icon = 'lock';
        $hint = 'Kartu tidak dapat dipakai transaksi';
    } elseif ($hasRfid) {
        $status = 'active';
        $label = 'Aktif';
        $icon = 'credit-card';
        $hint = 'Siap dipakai transaksi kantin';
    } else {
        $status = 'unregistered';
        $label = 'Belum Kartu';
        $icon = 'credit-card-off';
        $hint = 'RFID belum terdaftar';
    }
@endphp

<div class="rfid-card-status rfid-card-status--{{ $status }}" title="{{ $hint }}">
    <span class="rfid-card-status__icon" aria-hidden="true">
        <x-icon :name="$icon" size="sm" />
    </span>
    <span class="rfid-card-status__text">
        <span class="rfid-card-status__label">{{ $label }}</span>
        <span class="rfid-card-status__hint">{{ $hint }}</span>
    </span>
</div>
