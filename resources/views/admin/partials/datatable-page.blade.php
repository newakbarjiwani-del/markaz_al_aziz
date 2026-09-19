{{--
  Legacy include wrapper — prefer <x-admin.datatable-page> with filter/action slots.

  Pass: tableTitle, ajaxUrl, columns, showExport, defaultOrder, tableSubtitle?, exportFilename?, columnOptions?
--}}
<x-admin.datatable-page
    :title="$tableTitle ?? 'Data'"
    :subtitle="$tableSubtitle ?? null"
    :ajax-url="$ajaxUrl ?? ''"
    :columns="$columns ?? []"
    :show-export="$showExport ?? true"
    :export-filename="$exportFilename ?? null"
    :column-options="$columnOptions ?? []"
    :default-order="$defaultOrder ?? null"
/>
