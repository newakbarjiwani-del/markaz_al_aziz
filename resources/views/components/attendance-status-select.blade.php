@props([
    'type' => 'siswa',
    'includeAuto' => false,
    'id' => null,
    'name' => 'status',
    'class' => 'form-input max-w-xs',
    'required' => false,
])

@php
    $options = $type === 'guru'
        ? \App\Support\AttendanceStatus::guruManual()
        : \App\Support\AttendanceStatus::siswaManual();
@endphp

<select @if($id) id="{{ $id }}" @endif name="{{ $name }}" class="{{ $class }}" @if($required) required @endif>
    @if($includeAuto)
        <option value="">Otomatis (hadir / terlambat)</option>
    @endif
    @foreach($options as $status)
        <option value="{{ $status }}">{{ \App\Support\AttendanceStatus::label($status) }}</option>
    @endforeach
</select>
