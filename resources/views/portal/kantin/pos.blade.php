@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="kantin-pos-root"
     class="mx-auto max-w-xl"
     data-lookup-url="{{ $lookupUrl }}"
     data-charge-url="{{ $chargeUrl }}"
     data-face-references-url="{{ $faceReferencesUrl }}"
     data-face-model-url="{{ $faceModelUrl }}">
    <div class="card p-6">
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                <x-icon name="nfc" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Belanja Kantin</h2>
                <p class="text-muted text-sm">Scan RFID atau deteksi wajah siswa, masukkan total belanja, lalu simpan transaksi.</p>
            </div>
        </div>

        <form id="kantin-pos-form" class="space-y-4" novalidate>
            <div>
                <p class="form-label mb-2">Metode pilih siswa</p>
                <div class="visitor-segment" role="tablist" aria-label="Metode pilih siswa">
                    <button type="button" class="visitor-segment__btn is-active" data-kantin-method="rfid" role="tab" aria-selected="true">
                        <x-icon name="nfc" size="sm" class="mr-1" /> Scan RFID
                    </button>
                    <button type="button" class="visitor-segment__btn" data-kantin-method="face" role="tab" aria-selected="false">
                        <x-icon name="face-id" size="sm" class="mr-1" /> Deteksi Wajah
                    </button>
                </div>
            </div>

            <div data-kantin-panel="rfid">
                <label class="form-label" for="kantin-rfid-input">RFID Siswa</label>
                <input type="text"
                       id="kantin-rfid-input"
                       name="rfid_uid"
                       class="form-input font-mono"
                       placeholder="Scan kartu RFID..."
                       autocomplete="off"
                       required>
            </div>

            <div data-kantin-panel="face" class="hidden space-y-3">
                {{-- Siswa hasil deteksi wajah dikirim lewat input tersembunyi ini (name=siswa_id). --}}
                <input type="hidden" name="siswa_id" id="kantin-face-siswa" value="" disabled>
                <div>
                    <label class="form-label" for="kantin-face-kelas">Batasi kelas (disarankan)</label>
                    <select id="kantin-face-kelas" class="form-input">
                        <option value="">Semua kelas ({{ $classes->count() }})</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-muted mt-1 text-xs">Pilih kelas agar deteksi lebih cepat & akurat bila siswa banyak.</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <button type="button" id="kantin-face-toggle" class="btn-primary h-11">
                        <x-icon name="camera" size="sm" class="mr-1" /> Mulai Kamera
                    </button>
                    <select id="kantin-face-camera-mode" class="form-input h-11">
                        <option value="user">Kamera Depan</option>
                        <option value="environment">Kamera Belakang</option>
                    </select>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-900 to-slate-950 p-2 dark:border-slate-800">
                    <div class="relative mx-auto w-full overflow-hidden rounded-2xl border border-white/15 bg-black">
                        <div class="relative aspect-video">
                            <video id="kantin-face-video" class="h-full w-full object-cover" autoplay muted playsinline></video>
                            <canvas id="kantin-face-overlay" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400" id="kantin-face-status">Arahkan wajah siswa ke kamera. Total belanja diisi setelah siswa terdeteksi.</p>
            </div>

            <div id="kantin-student-summary" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                <p class="font-medium text-slate-900 dark:text-white" id="kantin-student-name">-</p>
                <p class="text-muted mt-1" id="kantin-student-meta">-</p>
            </div>

            <div>
                <label class="form-label" for="kantin-saldo-input">Saldo Cashless</label>
                <input type="text"
                       id="kantin-saldo-input"
                       class="form-input bg-slate-50 font-medium text-slate-900 dark:bg-slate-900/60 dark:text-white"
                       value="Rp 0"
                       readonly
                       tabindex="-1">
            </div>

            <div>
                <label class="form-label" for="kantin-bill-input">Total Belanja</label>
                <x-form.amount name="amount" id="kantin-bill-input" :min="1" placeholder="0" required />
            </div>

            <div>
                <label class="form-label" for="kantin-description-input">Keterangan</label>
                <input type="text"
                       id="kantin-description-input"
                       name="description"
                       class="form-input"
                       maxlength="255"
                       placeholder="Contoh: Nasi goreng + es teh">
            </div>

            <button type="submit" id="kantin-submit-btn" class="btn-primary w-full" disabled>
                <x-icon name="credit-card" size="sm" class="mr-1" /> Simpan Transaksi
            </button>

            <p class="text-muted text-xs">
                Limit harian siswa dicek terlebih dahulu, kemudian limit global sekolah.
            </p>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/kantin-pos.js') }}?v=6"></script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="{{ asset('js/kantin-pos-face.js') }}?v=1"></script>
@endpush
