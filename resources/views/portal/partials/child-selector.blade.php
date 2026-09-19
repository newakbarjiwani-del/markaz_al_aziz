@if($children->count() > 1)
<form id="filter-form" method="GET" data-filter-mode="navigate" class="filter-form">
    <div>
        <label class="form-label">Pilih Anak</label>
        <select name="siswa_id" class="form-input">
            <option value="">Semua anak</option>
            @foreach($children as $child)
                <option value="{{ $child->id }}" @selected((int) request('siswa_id') === $child->id)>
                    {{ $child->nis }} — {{ $child->name }}@if($child->kelas) ({{ $child->kelas->name }})@endif
                </option>
            @endforeach
        </select>
    </div>
    <x-filter-actions />
</form>
@endif
