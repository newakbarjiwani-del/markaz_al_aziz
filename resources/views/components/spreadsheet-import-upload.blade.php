@props([
    'name' => 'file',
    'label' => 'File Excel',
])

<div {{ $attributes->merge(['class' => 'spreadsheet-import-field']) }}>
    <label class="form-label" for="{{ $name }}-input">{{ $label }}</label>
    <div class="spreadsheet-upload-zone" data-spreadsheet-upload-zone>
        <input type="file"
               id="{{ $name }}-input"
               name="{{ $name }}"
               data-spreadsheet-import
               class="spreadsheet-upload-zone__input"
               accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
        <div class="spreadsheet-upload-zone__empty" data-spreadsheet-upload-empty>
            <span class="spreadsheet-upload-zone__icon" aria-hidden="true">
                <x-icon name="file-spreadsheet" size="lg" />
            </span>
            <p class="spreadsheet-upload-zone__title">Seret & lepas file Excel di sini</p>
            <p class="spreadsheet-upload-zone__hint">atau</p>
            <button type="button" class="btn-secondary btn-sm" data-spreadsheet-upload-browse>
                Pilih File
            </button>
        </div>
        <div class="spreadsheet-upload-zone__selected" data-spreadsheet-upload-selected>
            <div class="spreadsheet-upload-zone__file">
                <span class="spreadsheet-upload-zone__file-icon" aria-hidden="true">
                    <x-icon name="file-spreadsheet" size="md" />
                </span>
                <div class="min-w-0">
                    <p class="spreadsheet-upload-zone__file-name" data-spreadsheet-upload-name></p>
                    <p class="spreadsheet-upload-zone__file-size" data-spreadsheet-upload-size></p>
                </div>
            </div>
            <div class="spreadsheet-upload-zone__actions">
                <button type="button" class="btn-secondary btn-sm" data-spreadsheet-upload-replace>
                    <x-icon name="refresh" size="sm" class="mr-1" /> Ganti File
                </button>
                <button type="button" class="btn-secondary btn-sm" data-spreadsheet-upload-clear>
                    <x-icon name="x" size="sm" class="mr-1" /> Hapus
                </button>
            </div>
        </div>
    </div>
    <p class="text-muted mt-1 text-xs">Excel .xlsx atau .xls · maks {{ \App\Support\ImportSpreadsheetRules::MAX_FILE_SIZE_LABEL }}.</p>
</div>
