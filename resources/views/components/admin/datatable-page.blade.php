{{--
  Unified DataTable card (header + optional filters + table), matching Data Siswa layout.

  Usage:
    <x-admin.datatable-page title="..." :ajax-url="..." :columns="[...]">
        <x-slot:actions>...</x-slot:actions>
        <x-slot:filters>
            <form id="filter-form" class="filter-form">...</form>
        </x-slot:filters>
    </x-admin.datatable-page>

  Slots: actions (optional header buttons beside export), filters (optional filter form).
  Export uses <x-dropdown-button> by default (ids export-excel / export-pdf for export.js).
  Set :export-dropdown="false" to keep separate Excel/PDF buttons.
--}}
@props([
    'title' => 'Data',
    'subtitle' => null,
    'ajaxUrl' => '',
    'columns' => [],
    'showExport' => true,
    'exportDropdown' => true,
    'exportFilename' => null,
    'columnOptions' => [],
    'defaultOrder' => null,
])

@php
    $resolvedColumnOptions = collect($columns)->values()->map(function ($col, $idx) use ($columnOptions) {
        $isAction = $col === 'Aksi';
        $default = [
            'orderable' => ! $isAction,
            'searchable' => ! $isAction,
            'exportable' => ! $isAction,
            'html' => false,
        ];

        if (isset($columnOptions[$idx]) && is_array($columnOptions[$idx])) {
            return array_merge($default, $columnOptions[$idx]);
        }

        return $default;
    })->values()->all();
    $tableMinWidth = max(32, (int) (count($columns) * 5.5)).'rem';
    $hasFilters = isset($filters) && ! $filters->isEmpty();
    $hasActions = isset($actions) && ! $actions->isEmpty();
@endphp

<div {{ $attributes->class(['card', 'card--datatable']) }}>
    <div class="card-divider flex flex-col gap-3 p-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 class="card-title">{{ $title }}</h2>
            @if($subtitle)
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>
        @if($showExport || $hasActions)
            <div class="datatable-page__toolbar">
                @if($showExport)
                    <div class="datatable-page__toolbar-group datatable-page__toolbar-group--export">
                        @if($exportDropdown)
                            <x-dropdown-button label="Export" icon="download" variant="secondary" menu-label="Export data">
                                <x-dropdown-button.item id="export-excel" icon="file-spreadsheet">Excel</x-dropdown-button.item>
                                <x-dropdown-button.item id="export-pdf" icon="file-type-pdf">PDF</x-dropdown-button.item>
                            </x-dropdown-button>
                        @else
                            <button type="button" id="export-excel" class="btn-secondary">
                                <x-icon name="file-spreadsheet" size="sm" class="mr-1" /> Excel
                            </button>
                            <button type="button" id="export-pdf" class="btn-secondary">
                                <x-icon name="file-type-pdf" size="sm" class="mr-1" /> PDF
                            </button>
                        @endif
                    </div>
                @endif
                @if($hasActions)
                    <div class="datatable-page__toolbar-group datatable-page__toolbar-group--actions">
                        {{ $actions }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if($hasFilters)
        <div class="filter-bar">
            {{ $filters }}
        </div>
    @endif

    <div @class(['card--datatable__body', 'p-4', 'pt-0' => ! $hasFilters])>
        <table id="main_table"
               class="datatable-main w-full display"
               style="--table-min-width: {{ $tableMinWidth }}"
               data-ajax-url="{{ $ajaxUrl }}"
               data-column-options="{{ json_encode($resolvedColumnOptions) }}"
               @if($defaultOrder) data-default-order="{{ json_encode($defaultOrder) }}" @endif
               @if($exportFilename) data-export-filename="{{ $exportFilename }}" @endif>
            <thead>
                <tr>
                    @foreach($columns as $col)
                        <th @class(['dt-col-actions' => $col === 'Aksi'])>{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
</div>
