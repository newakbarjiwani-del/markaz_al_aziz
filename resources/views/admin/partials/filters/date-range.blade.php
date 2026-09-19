@props([
    'from' => null,
    'to' => null,
    'fromName' => 'date_from',
    'toName' => 'date_to',
    'fromId' => null,
    'toId' => null,
    'fromLabel' => 'Dari Tanggal',
    'toLabel' => 'Sampai Tanggal',
    'colClass' => '',
])

<div class="{{ $colClass }}">
    <label class="form-label" for="{{ $fromId ?? $fromName }}">{{ $fromLabel }}</label>
    <input type="date" name="{{ $fromName }}" id="{{ $fromId ?? $fromName }}" value="{{ $from }}" class="form-input">
</div>
<div class="{{ $colClass }}">
    <label class="form-label" for="{{ $toId ?? $toName }}">{{ $toLabel }}</label>
    <input type="date" name="{{ $toName }}" id="{{ $toId ?? $toName }}" value="{{ $to }}" class="form-input">
</div>
