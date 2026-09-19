@props(['siswa'])

<form data-fetch-form
      method="POST"
      action="{{ route('admin.dompet-digital.rfid-kontrol.update-block', $siswa) }}"
      class="rfid-block-toggle">
    @csrf
    @method('PATCH')
    <input type="hidden" name="rfid_blocked" value="{{ $siswa->isRfidBlocked() ? '0' : '1' }}">

    @if($siswa->isRfidBlocked())
        <button type="submit"
                class="rfid-block-toggle__btn rfid-block-toggle__btn--activate"
                title="Aktifkan kembali kartu RFID siswa ini">
            <x-icon name="lock-open" size="sm" />
            <span>Aktifkan Kartu</span>
        </button>
    @else
        <button type="submit"
                class="rfid-block-toggle__btn rfid-block-toggle__btn--block"
                title="Blokir kartu RFID agar tidak bisa dipakai transaksi">
            <x-icon name="lock" size="sm" />
            <span>Blokir Kartu</span>
        </button>
    @endif
</form>
