@props([
    'submitLabel' => 'Filter',
    'resetLabel' => 'Reset',
])

<div class="filter-form__actions">
    <button type="submit" class="btn-primary btn-sm">{{ $submitLabel }}</button>
    <button type="reset" class="btn-secondary btn-sm">{{ $resetLabel }}</button>
</div>
