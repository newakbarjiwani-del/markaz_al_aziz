<form id="filter-form" class="filter-form">
    @if(isset($children) && $children->count() > 1)
        <div>
            <label class="form-label">Pilih Anak</label>
            <select name="siswa_id" class="form-input w-full">
                <option value="">Semua anak</option>
                @foreach($children as $child)
                    <option value="{{ $child->id }}" @selected((int) request('siswa_id') === $child->id)>
                        {{ $child->nis }} — {{ $child->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
    @include('admin.partials.filters.date-range', [
        'from' => request('date_from'),
        'to' => request('date_to'),
        'colClass' => '',
    ])
    <x-filter-actions />
</form>
