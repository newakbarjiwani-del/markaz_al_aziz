@php
    $asalLabel = str_contains($prefix, 'karyawan') ? 'Unit Kerja' : 'Asal / Instansi';
    $title = $title ?? null;
@endphp
@if($title)
    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $title }}</p>
@endif
<div class="grid gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="form-label" for="{{ $prefix }}-nama">Nama <span class="text-red-500">*</span></label>
        <input type="text" id="{{ $prefix }}-nama" class="form-input" maxlength="255" placeholder="Nama lengkap" required>
    </div>
    <div>
        <label class="form-label" for="{{ $prefix }}-asal">{{ $asalLabel }}</label>
        <input type="text" id="{{ $prefix }}-asal" class="form-input" maxlength="255" placeholder="{{ str_contains($prefix, 'karyawan') ? 'Contoh: Tata Usaha' : 'Sekolah / instansi' }}">
    </div>
    <div>
        <label class="form-label" for="{{ $prefix }}-telepon">Telepon</label>
        <input type="text" id="{{ $prefix }}-telepon" class="form-input" maxlength="30" placeholder="08xxxxxxxxxx">
    </div>
    <div class="md:col-span-2">
        <label class="form-label" for="{{ $prefix }}-catatan">Catatan</label>
        <input type="text" id="{{ $prefix }}-catatan" class="form-input" maxlength="500" placeholder="Opsional — keperluan kunjungan">
    </div>
</div>
