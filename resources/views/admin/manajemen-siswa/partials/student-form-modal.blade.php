@props([
    'classes',
    'kamarList' => collect(),
    'statusSantriList' => collect(),
    'reloadPage' => false,
])

@include('admin.partials.profile-photo-assets')
<x-modal id="student-modal" title="Tambah / Ubah Siswa" size="xl">
    <form id="student-form"
          data-fetch-form
          data-confirm-submit
          data-confirm-builder="buildStudentNisUpdateConfirm"
          @if($reloadPage) data-reload-page @else data-reload-table @endif
          data-close-modal="student-modal"
          data-default-action="{{ route('admin.manajemen-siswa.data-siswa.store') }}"
          action="{{ route('admin.manajemen-siswa.data-siswa.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div class="grid gap-5 lg:grid-cols-2">
            <section class="form-section">
                <h4 class="form-section__title">Identitas</h4>
                <div class="form-section__body space-y-4">
                    <div>
                        <label class="form-label" for="student-nis">NIS</label>
                        <input type="text"
                               name="nis"
                               id="student-nis"
                               class="form-input font-mono"
                               inputmode="numeric"
                               pattern="[0-9]*"
                               maxlength="{{ \App\Support\VirtualAccountNumber::NIS_MAX_LENGTH }}"
                               placeholder="Hanya angka, maks. {{ \App\Support\VirtualAccountNumber::NIS_MAX_LENGTH }} digit"
                               required>
                        <p class="text-muted mt-1 text-xs">NIS numerik (maks. {{ \App\Support\VirtualAccountNumber::NIS_MAX_LENGTH }} digit). No. VA memakai 10 digit terakhir.</p>
                        <p class="field-warning mt-1 flex items-start gap-1.5 text-xs" role="note">
                            <x-icon name="alert-triangle" size="sm" class="mt-0.5 shrink-0" />
                            <span>Mengubah NIS dapat mengubah nomor virtual account siswa. Pastikan data sudah benar sebelum menyimpan.</span>
                        </p>
                    </div>
                    <div>
                        <label class="form-label">No. Virtual Account</label>
                        <input type="text"
                               id="student-va-display"
                               class="form-input font-mono"
                               value=""
                               readonly
                               tabindex="-1"
                               placeholder="Otomatis dari NIS">
                        <p class="text-muted mt-1 text-xs">Awalan 6 digit + 10 digit terakhir NIS (ditambah nol di depan bila kurang dari 10 digit).</p>
                        <div id="student-va-suffix-warning"
                             class="alert alert-warning mt-2 hidden"
                             role="alert"
                             data-va-check-url="{{ route('admin.manajemen-siswa.data-siswa.check-va-suffix') }}">
                            <div class="flex items-start gap-2">
                                <x-icon name="alert-triangle" size="sm" class="mt-0.5 shrink-0" />
                                <p id="student-va-suffix-warning-text" class="text-sm"></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="student-name">Nama</label>
                        <input type="text" name="name" id="student-name" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label" for="student-kelas">Kelas</label>
                        <select name="kelas_id" id="student-kelas" class="form-input">
                            <option value="">Pilih kelas</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label" for="student-kamar">Kamar</label>
                            <select name="kamar_id" id="student-kamar" class="form-input">
                                <option value="">—</option>
                                @foreach($kamarList as $kamar)
                                    <option value="{{ $kamar->id }}">{{ $kamar->displayLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="student-status-santri">Status Santri</label>
                            <select name="status_santri_id" id="student-status-santri" class="form-input">
                                <option value="">—</option>
                                @foreach($statusSantriList as $statusSantri)
                                    <option value="{{ $statusSantri->id }}">{{ $statusSantri->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label" for="student-gender">Jenis Kelamin</label>
                            <select name="gender" id="student-gender" class="form-input">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="student-status">Status</label>
                            <select name="status" id="student-status" class="form-input">
                                <option value="{{ \App\Models\Siswa::STATUS_ACTIVE }}">Aktif</option>
                                <option value="{{ \App\Models\Siswa::STATUS_PENDING }}">Menunggu</option>
                                <option value="{{ \App\Models\Siswa::STATUS_INACTIVE }}">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <h4 class="form-section__title">Detail & Cashless</h4>
                <div class="form-section__body space-y-4">
                    <div>
                        <label class="form-label" for="student-rfid-input">RFID Cashless</label>
                        <input type="text"
                               name="rfid_uid"
                               id="student-rfid-input"
                               class="form-input font-mono"
                               maxlength="64"
                               placeholder="Scan kartu RFID (opsional)"
                               autocomplete="off"
                               data-rfid-scan-field>
                        <p class="text-muted mt-1 text-xs">Opsional. Fokus ke field ini lalu scan kartu — Enter dari scanner tidak akan mengirim form. Wajib diisi sebelum siswa bertransaksi di kantin.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Limit Harian Siswa (Rp)</label>
                            <x-form.amount name="daily_transaction_limit" :id="'student-daily-limit'" :min="0" placeholder="Kosongkan = global" />
                        </div>
                        <div>
                            <x-form.checkbox name="rfid_blocked" label="Blokir RFID cashless" />
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="student-birth-date">Tanggal Lahir</label>
                        <input type="date" name="birth_date" id="student-birth-date" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="student-address">Alamat</label>
                        <textarea name="address" id="student-address" class="form-input" rows="2" placeholder="Alamat tempat tinggal siswa"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Foto Profil</label>
                        <x-profile-photo-upload />
                    </div>
                </div>
            </section>
        </div>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="student-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
