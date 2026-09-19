<x-modal id="profil-siswa-modal" title="Ubah Profil Siswa">
    <form id="profil-siswa-form"
          data-fetch-form
          data-reload-table
          data-close-modal="profil-siswa-modal"
          action="#"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Identitas</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="profil-siswa-nis">NIS</label>
                    <input type="text" id="profil-siswa-nis" class="form-input font-mono" readonly tabindex="-1">
                </div>
                <div>
                    <label class="form-label" for="profil-siswa-name">Nama</label>
                    <input type="text" id="profil-siswa-name" class="form-input" readonly tabindex="-1">
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Profil Tambahan</h4>
            <p class="text-muted mb-4 text-sm">Field ini tidak tersedia di form Data Siswa.</p>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="profil-siswa-nama-panggilan">Nama Panggilan</label>
                    <input type="text"
                           name="nama_panggilan"
                           id="profil-siswa-nama-panggilan"
                           class="form-input"
                           maxlength="100"
                           placeholder="Contoh: Ahmad">
                </div>
                <div>
                    <label class="form-label" for="profil-siswa-golongan-darah">Golongan Darah</label>
                    <select name="golongan_darah" id="profil-siswa-golongan-darah" class="form-input">
                        <option value="">— Pilih —</option>
                        @foreach (['A', 'B', 'AB', 'O', 'A+', 'B+', 'AB+', 'O+', 'A-', 'B-', 'AB-', 'O-'] as $gol)
                            <option value="{{ $gol }}">{{ $gol }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="profil-siswa-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>

@push('scripts')
<script>
document.addEventListener('edit-record-populated', function (event) {
    if (event.detail?.form?.id !== 'profil-siswa-form') {
        return;
    }

    var record = event.detail.record || {};
    var nisInput = document.getElementById('profil-siswa-nis');
    var nameInput = document.getElementById('profil-siswa-name');

    if (nisInput) {
        nisInput.value = record.nis ?? '';
    }

    if (nameInput) {
        nameInput.value = record.name ?? '';
    }
});
</script>
@endpush
