@props([
    'schools' => [],
    'selected' => null,
    'name' => 'sekolah_id',
    'id' => null,
    'label' => 'Sekolah',
    'colClass' => '',
    'allowAll' => true,
    'required' => false,
])

<div class="{{ $colClass }}">
    <label class="form-label" for="{{ $id ?? $name }}">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $id ?? $name }}" class="form-input" data-s2 data-filter-sekolah @if($required) required @endif>
        @if($allowAll)
            <option value="">Semua Sekolah</option>
        @else
            <option value="">Pilih sekolah</option>
        @endif
        @foreach($schools as $school)
            <option value="{{ $school->id }}" @selected((string) $selected === (string) $school->id)>
                {{ $school->name }}
            </option>
        @endforeach
    </select>
</div>
