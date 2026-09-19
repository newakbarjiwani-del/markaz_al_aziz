@php
    $stats = $stats ?? ['waiting' => 0, 'overdue' => 0, 'due_today' => 0, 'fine_estimate' => 0];
@endphp

<div class="library-loan-stats mb-6">
    <div class="library-loan-stats__card library-loan-stats__card--blue">
        <div class="library-loan-stats__icon"><x-icon name="books" /></div>
        <div>
            <p class="library-loan-stats__label">Menunggu Kembali</p>
            <p class="library-loan-stats__value">{{ number_format($stats['waiting']) }}</p>
        </div>
    </div>
    <div class="library-loan-stats__card library-loan-stats__card--red">
        <div class="library-loan-stats__icon"><x-icon name="alert-triangle" /></div>
        <div>
            <p class="library-loan-stats__label">Terlambat</p>
            <p class="library-loan-stats__value">{{ number_format($stats['overdue']) }}</p>
        </div>
    </div>
    <div class="library-loan-stats__card library-loan-stats__card--amber">
        <div class="library-loan-stats__icon"><x-icon name="calendar-event" /></div>
        <div>
            <p class="library-loan-stats__label">Jatuh Tempo Hari Ini</p>
            <p class="library-loan-stats__value">{{ number_format($stats['due_today']) }}</p>
        </div>
    </div>
    <div class="library-loan-stats__card library-loan-stats__card--green">
        <div class="library-loan-stats__icon"><x-icon name="coin" /></div>
        <div>
            <p class="library-loan-stats__label">Estimasi Denda</p>
            <p class="library-loan-stats__value library-loan-stats__value--sm">Rp {{ number_format($stats['fine_estimate'], 0, ',', '.') }}</p>
        </div>
    </div>
</div>
