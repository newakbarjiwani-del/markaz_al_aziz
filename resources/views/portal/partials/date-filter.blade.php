<form id="filter-form" class="filter-form">
    @include('admin.partials.filters.date-range', [
        'from' => request('date_from'),
        'to' => request('date_to'),
        'colClass' => '',
    ])
    <x-filter-actions />
</form>
