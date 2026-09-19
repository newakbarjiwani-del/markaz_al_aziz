<form id="filter-form" class="filter-form">
    @if(isset($children) && $children->count() > 1)
        <div>
            <label class="form-label" for="filter-siswa_id">Pilih Anak</label>
            <select name="siswa_id" id="filter-siswa_id" class="form-input w-full">
                <option value="">Semua anak</option>
                @foreach($children as $child)
                    <option value="{{ $child->id }}" @selected((int) request('siswa_id') === $child->id)>
                        {{ $child->nis }} — {{ $child->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label class="form-label" for="filter-status">Status</label>
        <select name="status" id="filter-status" class="form-input">
            <option value="">Semua</option>
            <option value="0" @selected((string) request('status') === '0')>Belum Lunas</option>
            <option value="2" @selected((string) request('status') === '2')>Cicilan</option>
            <option value="1" @selected((string) request('status') === '1')>Lunas</option>
        </select>
    </div>
    <div>
        <label class="form-label" for="filter-jenis">Jenis Tagihan</label>
        <select name="jenis" id="filter-jenis" class="form-input">
            <option value="">Semua</option>
            @foreach($jenisOptions as $jenis)
                <option value="{{ $jenis }}" @selected(request('jenis') === $jenis)>{{ $jenis }}</option>
            @endforeach
        </select>
    </div>
    @include('admin.partials.filters.date-range', [
        'from' => request('date_from'),
        'to' => request('date_to'),
        'fromId' => 'filter-date-from',
        'toId' => 'filter-date-to',
        'fromLabel' => 'Jatuh Tempo Dari',
        'toLabel' => 'Jatuh Tempo Sampai',
        'colClass' => '',
    ])
    <x-filter-actions />
</form>
