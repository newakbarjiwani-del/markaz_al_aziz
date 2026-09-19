<span class="face-status-badge {{ $hasFotoWajah ? 'face-status-badge--done' : 'face-status-badge--pending' }}">
    <x-icon name="{{ $hasFotoWajah ? 'circle-check' : 'camera-off' }}" size="xs" />
    {{ $hasFotoWajah ? 'Sudah' : 'Belum' }}
</span>
