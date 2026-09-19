@props([
    'tagihan' => collect(),
    'showStudent' => false,
    'emptyMessage' => 'Belum ada data tagihan.',
])

<div {{ $attributes->class(['card dashboard-table-card portal-dashboard-panel']) }}>
    {{ $slot }}

    <div class="table-scroll">
        <table class="portal-dashboard-table w-full text-sm">
            <thead>
                <tr>
                    @if($showStudent)
                        <th>Siswa</th>
                    @endif
                    <th>Jenis</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihan as $item)
                    <tr>
                        @if($showStudent)
                            <td>
                                <p class="font-medium">{{ $item->siswa?->name ?? '-' }}</p>
                                <p class="text-muted text-xs">{{ $item->siswa?->nis }}</p>
                            </td>
                        @endif
                        <td class="text-muted">{{ $item->jenis }}</td>
                        <td class="text-right font-medium whitespace-nowrap">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge {{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showStudent ? 4 : 3 }}" class="portal-dashboard-table__empty">{{ $emptyMessage }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
