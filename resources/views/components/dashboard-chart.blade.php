@props(['id', 'title'])

<div {{ $attributes->merge(['class' => 'chart-panel card']) }}>
    <h3 class="chart-panel__title">{{ $title }}</h3>
    <div class="chart-panel__canvas">
        <canvas id="{{ $id }}"></canvas>
    </div>
</div>
