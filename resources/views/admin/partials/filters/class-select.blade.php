@props([
    'classes' => [],
    'selected' => null,
    'name' => 'kelas_id',
    'id' => null,
    'label' => 'Kelas',
    'colClass' => '',
])

<div class="{{ $colClass }}">
    <label class="form-label" for="{{ $id ?? $name }}">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $id ?? $name }}" class="form-input" data-s2>
        <option value="">Semua Kelas</option>
        @foreach($classes as $class)
            <option value="{{ $class->id }}"
                    data-sekolah-id="{{ $class->sekolah_id }}"
                    @selected((string) $selected === (string) $class->id)>
                {{ $class->name }}@if($class->sekolah) ({{ $class->sekolah->name }})@endif
            </option>
        @endforeach
    </select>
</div>
