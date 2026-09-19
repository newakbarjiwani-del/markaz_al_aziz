@props([
    'name' => 'photo',
    'label' => 'Foto Profil',
    'existingUrl' => null,
])

<div {{ $attributes->merge(['class' => 'profile-photo-field']) }}>
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    <input type="file"
           name="{{ $name }}"
           id="{{ $name }}"
           data-profile-photo
           @if($existingUrl) data-existing-url="{{ $existingUrl }}" @endif
           accept="image/jpeg,image/png,image/webp">
    <p class="text-muted mt-1 text-xs">Untuk kartu pelajar, profil, dan tampilan identitas — bukan foto absensi.</p>
    <p class="text-muted text-xs">JPEG, PNG, atau WebP · min 200×200 px · maks 2 MB.</p>
</div>
