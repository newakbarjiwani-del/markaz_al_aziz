@props([
    'title' => '',
    'icon' => 'award',
    'tone' => 'success', // success | danger
    'rows' => [],
    'empty' => 'Belum ada data.',
])

<section @class([
    'pp-rank card',
    'pp-rank--success' => $tone === 'success',
    'pp-rank--danger' => $tone === 'danger',
])>
    <div class="pp-rank__head">
        <span class="pp-rank__icon" aria-hidden="true">
            <x-icon :name="$icon" size="sm" />
        </span>
        <h3 class="pp-rank__title">{{ $title }}</h3>
    </div>

    @if(empty($rows))
        <p class="pp-rank__empty">{{ $empty }}</p>
    @else
        <ol class="pp-rank__list">
            @foreach($rows as $index => $row)
                <li @class([
                    'pp-rank__row',
                    'pp-rank__row--top' => $index < 3,
                ])>
                    <span class="pp-rank__badge">{{ $index + 1 }}</span>
                    <div class="pp-rank__body">
                        <p class="pp-rank__name">{{ $row['name'] }}</p>
                        @if(!empty($row['subtitle']))
                            <p class="pp-rank__subtitle">{{ $row['subtitle'] }}</p>
                        @endif
                    </div>
                    <div class="pp-rank__stats">
                        <p class="pp-rank__count">{{ $row['total'] }}×</p>
                        <p class="pp-rank__points">{{ number_format((int) $row['total_point']) }} poin</p>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
