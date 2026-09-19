@php
    $index = $index ?? 0;
    $fine = $fine ?? ['kondisi' => '', 'label' => '', 'amount' => 0];
@endphp

<div class="library-fine-row rounded-lg border border-slate-200 p-4 dark:border-slate-700" data-fine-row>
    <div class="grid gap-3 md:grid-cols-12">
        <div class="md:col-span-3">
            <label class="form-label">Kondisi</label>
            <select name="kondisi_fines[{{ $index }}][kondisi]" class="form-input" required>
                @foreach($kondisiChoices as $value => $label)
                    <option value="{{ $value }}" @selected(($fine['kondisi'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-5">
            <label class="form-label">Label di pengembalian</label>
            <input type="text"
                   name="kondisi_fines[{{ $index }}][label]"
                   value="{{ $fine['label'] ?? '' }}"
                   class="form-input"
                   maxlength="100"
                   required>
        </div>
        <div class="md:col-span-3">
            <label class="form-label">Nominal (Rp)</label>
            <x-form.amount :name="'kondisi_fines['.$index.'][amount]'" :value="$fine['amount'] ?? 0" :min="0" />
        </div>
        <div class="flex items-end justify-end md:col-span-1">
            <button type="button" class="btn-secondary btn-icon-only" data-remove-fine-row title="Hapus baris" aria-label="Hapus denda">
                <x-icon name="trash" size="sm" />
            </button>
        </div>
    </div>
</div>
