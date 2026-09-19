@props(['siswa'])

@php
    $pinSet = filled($siswa->cashless_pin);
    $hasRfid = $siswa->hasRfid();
    if ($siswa->isRfidBlocked()) {
        $cardStatus = 'Diblokir';
    } elseif ($hasRfid) {
        $cardStatus = 'Aktif';
    } else {
        $cardStatus = 'Belum Kartu';
    }
@endphp

<div class="action-group" role="group" aria-label="Aksi RFID & PIN">
    {{-- RFID UID --}}
    <button type="button"
            data-edit-record="{{ \App\Support\EditRecordPayload::encode([
                'rfid_uid' => $siswa->rfidUid(),
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name ?? '-',
                'card_status' => $cardStatus,
            ]) }}"
            data-update-url="{{ route('admin.dompet-digital.rfid-kontrol.update-rfid', $siswa) }}"
            data-form-target="rfid-update-form"
            data-modal-target="rfid-update-modal"
            data-modal-title="Ubah RFID"
            data-siswa-show-url="{{ route('admin.manajemen-siswa.data-siswa.show', $siswa) }}"
            class="btn-action btn-action-edit"
            title="Ubah UID kartu RFID"
            aria-label="Ubah UID kartu RFID">
        <x-icon name="nfc" size="sm" />
        <span class="btn-action-label">UID</span>
    </button>

    {{-- Blokir / aktifkan kartu --}}
    @if($siswa->isRfidBlocked())
        <form data-fetch-form
              method="POST"
              action="{{ route('admin.dompet-digital.rfid-kontrol.update-block', $siswa) }}"
              class="inline"
              data-reload-table>
            @csrf
            @method('PATCH')
            <input type="hidden" name="rfid_blocked" value="0">
            <button type="submit"
                    class="btn-action btn-action-activate"
                    title="Aktifkan kembali kartu RFID"
                    aria-label="Aktifkan kembali kartu RFID">
                <x-icon name="lock-open" size="sm" />
                <span class="btn-action-label">Aktifkan</span>
            </button>
        </form>
    @else
        @php
            $rfidBlockConfirmDetail = [
                ['label' => 'Siswa', 'value' => $siswa->name],
                ['label' => 'NIS', 'value' => $siswa->nis],
            ];
        @endphp
        <form data-fetch-form
              method="POST"
              action="{{ route('admin.dompet-digital.rfid-kontrol.update-block', $siswa) }}"
              class="inline"
              data-confirm-submit
              data-confirm-title="Blokir Kartu RFID"
              data-confirm-message="Kartu tidak bisa dipakai transaksi kantin sampai diaktifkan kembali."
              data-confirm-detail="{{ \App\Support\ConfirmDetail::attr($rfidBlockConfirmDetail) }}"
              data-confirm-text="Ya, Blokir"
              data-confirm-tone="warning"
              data-confirm-icon="ti-lock"
              data-confirm-header-icon="ti-lock"
              data-confirm-footnote="Kartu dapat diaktifkan kembali kapan saja dari halaman ini."
              data-reload-table>
            @csrf
            @method('PATCH')
            <input type="hidden" name="rfid_blocked" value="1">
            <button type="submit"
                    class="btn-action btn-action-block"
                    title="Blokir kartu RFID"
                    aria-label="Blokir kartu RFID">
                <x-icon name="lock" size="sm" />
                <span class="btn-action-label">Blokir</span>
            </button>
        </form>
    @endif

    {{-- Set / ubah PIN cashless --}}
    <button type="button"
            class="btn-action {{ $pinSet ? 'btn-action-account' : 'btn-action-account-missing' }}"
            title="{{ $pinSet ? 'Ubah PIN cashless' : 'Set PIN cashless' }}"
            aria-label="{{ $pinSet ? 'Ubah PIN cashless' : 'Set PIN cashless' }}"
            data-cashless-pin-edit
            data-siswa-id="{{ $siswa->id }}"
            data-siswa-label="{{ e($siswa->name) }}"
            data-siswa-nis="{{ e($siswa->nis) }}"
            data-pin-set="{{ $pinSet ? '1' : '0' }}"
            data-update-url="{{ route('admin.dompet-digital.cashless-pin.update', $siswa) }}">
        <x-icon name="{{ $pinSet ? 'password' : 'key' }}" size="sm" />
        <span class="btn-action-label">{{ $pinSet ? 'Ubah PIN' : 'Set PIN' }}</span>
    </button>

    {{-- Reset PIN (clear) --}}
    @if($pinSet)
        <button type="button"
                class="btn-action btn-action-reset"
                title="Reset PIN cashless"
                aria-label="Reset PIN cashless"
                data-cashless-pin-reset
                data-siswa-id="{{ $siswa->id }}"
                data-siswa-label="{{ e($siswa->name) }}"
                data-siswa-nis="{{ e($siswa->nis) }}"
                data-reset-url="{{ route('admin.dompet-digital.cashless-pin.reset', $siswa) }}">
            <x-icon name="key-off" size="sm" />
            <span class="btn-action-label">Reset</span>
        </button>
    @endif
</div>
