@php
    $finePerDay = $finePerDay ?? 2000;
    $kondisiFines = $kondisiFines ?? [];
    $kondisiChoices = $kondisiChoices ?? [];
    $formAction = $formAction ?? '#';
    $formMethod = $formMethod ?? 'PUT';
@endphp

<form data-fetch-form
      data-reset-on-success="false"
      data-reload-page
      id="library-fine-settings-form"
      action="{{ $formAction }}"
      method="POST"
      class="space-y-6">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div>
        <label for="setting-fine-per-day" class="form-label">Denda keterlambatan per hari (Rp)</label>
        <x-form.amount name="fine_per_day" :id="'setting-fine-per-day'" :value="$finePerDay" :min="0" />
        <p class="mt-1 text-xs text-slate-500">Dihitung otomatis saat pengembalian melewati jatuh tempo.</p>
    </div>

    <div>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Denda Kondisi Buku</h3>
                <p class="text-sm text-slate-500">Tambah aturan denda sesuai kondisi buku saat pengembalian.</p>
            </div>
            <button type="button" class="btn-secondary" id="add-kondisi-fine-row">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Denda
            </button>
        </div>

        <div id="kondisi-fine-rows" class="space-y-3">
            @forelse($kondisiFines as $index => $fine)
                @include('admin.perpustakaan.partials.setting-denda-row', [
                    'index' => $index,
                    'fine' => $fine,
                    'kondisiChoices' => $kondisiChoices,
                ])
            @empty
                <p id="kondisi-fine-empty" class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-600">
                    Belum ada denda kondisi. Klik <strong>Tambah Denda</strong> untuk menambahkan.
                </p>
            @endforelse
        </div>
    </div>

    <div class="flex justify-end pt-2">
        <button type="submit" class="btn-primary">
            <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
        </button>
    </div>
</form>

<template id="kondisi-fine-row-template">
    @include('admin.perpustakaan.partials.setting-denda-row', [
        'index' => '__INDEX__',
        'fine' => ['kondisi' => 'rusak_ringan', 'label' => 'Rusak ringan', 'amount' => ''],
        'kondisiChoices' => $kondisiChoices,
    ])
</template>
