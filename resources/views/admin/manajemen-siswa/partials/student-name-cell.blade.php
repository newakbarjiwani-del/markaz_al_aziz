@props([
    'name',
    'hasPelanggaran' => false,
    'hasPrestasi' => false,
])

<span class="student-name-cell">
    <span>{{ $name }}</span>
    @if($hasPrestasi)
        <span class="student-prestasi-badge" title="Memiliki catatan prestasi">
            <x-icon name="award" size="xs" />
            <span class="sr-only">Ada prestasi</span>
        </span>
    @endif
    @if($hasPelanggaran)
        <span class="student-violation-badge" title="Memiliki poin pelanggaran aktif">
            <x-icon name="gavel" size="xs" />
            <span class="sr-only">Ada pelanggaran aktif</span>
        </span>
    @endif
</span>
