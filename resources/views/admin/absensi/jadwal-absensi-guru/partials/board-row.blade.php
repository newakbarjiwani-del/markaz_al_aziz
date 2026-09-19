@php
    $stateClass = $row['is_complete'] ? 'is-done' : ($row['has_masuk'] ? 'is-partial' : 'is-pending');
    $initials = collect(explode(' ', $row['name'] ?? ''))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $pillClass = match($row['status'] ?? '') {
        'terlambat' => 'status-terlambat',
        'alpha' => 'status-alpha',
        'izin', 'sakit', 'cuti' => 'status-absent',
        'hadir' => 'status-hadir',
        default => $row['has_record'] ?? false ? 'status-hadir' : 'status-pending',
    };
@endphp
<div class="attendance-terminal__board-row {{ $stateClass }}" data-guru-row="{{ $row['id'] }}">
    <div class="attendance-terminal__board-avatar" aria-hidden="true">{{ $initials ?: '?' }}</div>
    <div class="attendance-terminal__board-body">
        <div class="attendance-terminal__board-head">
            <div class="min-w-0">
                <p class="attendance-terminal__board-name">{{ $row['name'] }}</p>
                <p class="attendance-terminal__board-meta">{{ $row['nip'] ?? '-' }} · {{ $row['jabatan'] ?? '-' }}</p>
            </div>
            @if($row['has_record'])
                <span class="attendance-terminal__status-pill {{ $pillClass }}">
                    {{ \App\Support\AttendanceStatus::label($row['status'] ?? 'hadir') }}
                </span>
            @else
                <span class="attendance-terminal__status-pill status-pending">Belum masuk</span>
            @endif
        </div>
        @if($row['has_record'])
            <div class="attendance-terminal__board-times">
                @if($row['is_absent_only'])
                    <span class="attendance-terminal__time-chip attendance-terminal__time-chip--muted">Manual · tanpa jam kehadiran</span>
                @elseif($row['has_masuk'])
                    <span class="attendance-terminal__time-chip">
                        <x-icon name="login" size="xs" /> Masuk {{ \App\Support\DisplayDate::time($row['jam_masuk']) }} · {{ strtoupper($row['method'] ?? '-') }}
                    </span>
                    @if($row['has_pulang'])
                        <span class="attendance-terminal__time-chip">
                            <x-icon name="logout" size="xs" /> Pulang {{ \App\Support\DisplayDate::time($row['jam_keluar']) }} · {{ ucfirst(str_replace('_', ' ', $row['status_pulang'] ?? '-')) }}
                        </span>
                    @else
                        <span class="attendance-terminal__time-chip attendance-terminal__time-chip--warn">Belum pulang</span>
                    @endif
                @endif
            </div>
        @endif
    </div>
</div>
