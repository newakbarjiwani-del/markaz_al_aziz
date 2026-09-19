@props(['modalId' => 'face-capture-modal', 'title' => 'Rekam Wajah (Absensi)'])

<x-modal :id="$modalId" :title="$title">
    <div class="face-modal">
        <div class="face-modal__student">
            <div class="face-modal__avatar" aria-hidden="true">
                <x-icon name="user" size="md" />
            </div>
            <div class="face-modal__meta">
                <p id="face-student-name" class="face-modal__name">-</p>
                <p class="face-modal__nis">NIS <span id="face-student-nis">-</span></p>
            </div>
            <span id="face-modal-status" class="face-status-badge face-status-badge--pending">Belum</span>
        </div>

        <div class="face-modal__stage" aria-live="polite">
            <div id="face-stage-empty" class="face-modal__empty">
                <x-icon name="camera" size="lg" />
                <p>Belum ada foto referensi absensi</p>
                <span class="face-modal__empty-hint">Tekan <strong>Mulai Kamera</strong> untuk rekam wajah</span>
            </div>
            <img id="face-existing-photo"
                 alt="Foto wajah tersimpan"
                 class="face-modal__media hidden" />
            <div id="face-camera-wrap" class="face-modal__media hidden">
                <video id="face-capture-video" autoplay playsinline muted></video>
            </div>
            <img id="face-capture-preview"
                 alt="Preview hasil rekam"
                 class="face-modal__media hidden" />
            <canvas id="face-capture-canvas" class="hidden"></canvas>
            <span id="face-stage-label" class="face-modal__stage-label">-</span>
        </div>

        <p class="face-modal__hint">Foto referensi untuk absensi dan verifikasi wajah — berbeda dari foto profil (kartu pelajar). Posisikan wajah di tengah frame. Format JPEG, maks. ±750 KB.</p>

        <div class="face-modal__toolbar">
            <select id="face-capture-camera-mode" class="form-input face-modal__camera-select" title="Pilih kamera">
                <option value="user">Kamera Depan</option>
                <option value="environment">Kamera Belakang</option>
            </select>
            <button type="button" class="btn-secondary btn-sm" id="face-start-camera">
                <x-icon name="camera" size="sm" class="mr-1" /> Mulai Kamera
            </button>
            <button type="button" class="btn-secondary btn-sm" id="face-capture-shot" disabled>
                <x-icon name="capture" size="sm" class="mr-1" /> Ambil Foto
            </button>
            <button type="button" class="btn-secondary btn-sm hidden" id="face-retake-shot">
                <x-icon name="refresh" size="sm" class="mr-1" /> Ulangi
            </button>
        </div>

        <div class="face-modal__footer">
            <button type="button" class="btn-primary" id="face-save-shot" disabled>
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
            <button type="button" class="btn-danger" id="face-delete-shot" disabled>
                <x-icon name="trash" size="sm" class="mr-1" /> Hapus
            </button>
            <button type="button" data-modal-close="{{ $modalId }}" class="btn-secondary">Tutup</button>
        </div>
    </div>
</x-modal>
