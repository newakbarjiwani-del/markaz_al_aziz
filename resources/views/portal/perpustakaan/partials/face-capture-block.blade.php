@php
    $optional = $optional ?? false;
@endphp
<div class="face-capture-shell face-capture-shell--doc">
    <div class="space-y-3">
        <div class="face-capture-shell__controls">
            <button type="button" id="{{ $prefix }}-face-toggle" class="btn-primary h-11">
                <x-icon name="camera" size="sm" class="mr-1" /> Mulai Kamera
            </button>
            <select id="{{ $prefix }}-face-camera-mode" class="form-input h-11">
                <option value="user">Kamera Depan</option>
                <option value="environment">Kamera Belakang</option>
            </select>
        </div>
        <div class="face-capture-frame">
            <div class="face-capture-frame__viewport">
                <video id="{{ $prefix }}-face-video" class="h-full w-full object-cover" autoplay muted playsinline></video>
            </div>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400" id="{{ $prefix }}-face-status">
            {{ $optional ? 'Ambil foto dokumentasi (tanpa deteksi wajah), lalu simpan kunjungan.' : 'Rekam wajah pengunjung.' }}
        </p>
        <button type="button" id="{{ $prefix }}-face-capture" class="btn-secondary" disabled>
            <x-icon name="camera" size="sm" class="mr-1" /> Ambil Foto
        </button>
    </div>
    <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pratinjau</p>
        <div id="{{ $prefix }}-face-preview" class="face-capture-preview">
            Foto belum diambil.
        </div>
    </div>
</div>
