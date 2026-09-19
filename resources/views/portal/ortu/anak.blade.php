@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $childrenData = $children->map(function ($child) {
        $extra = $child->profil?->extra_fields ?? [];

        return [
            'id' => $child->id,
            'name' => $child->name,
            'nis' => $child->nis,
            'kelas' => $child->kelas?->name ?? '-',
            'gender' => $child->gender,
            'birth_date' => $child->birth_date?->format('Y-m-d') ?? '',
            'birth_place' => $child->birth_place ?? '',
            'address' => $child->address ?? '',
            'nama_panggilan' => $extra['nama_panggilan'] ?? '',
            'golongan_darah' => $extra['golongan_darah'] ?? '',
        ];
    });
    echo '<script>window.childrenDataMap = ' . json_encode($childrenData) . ';</script>';
@endphp

@if($children->isEmpty())
    <div class="card p-6 text-center text-muted">
        <p>Belum ada data anak yang terhubung ke akun Anda.</p>
    </div>
@else
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($children as $child)
            @php $extra = $child->profil?->extra_fields ?? []; @endphp
            <div class="card portal-child-profile">
                <div class="portal-child-profile__layout">
                    <div class="portal-child-profile__photo">
                        <div class="portal-child-profile__avatar" aria-hidden="true">
                            @if($child->profil?->photoUrl())
                                <img src="{{ $child->profil->photoUrl() }}" alt="Foto profil {{ $child->name }}" class="portal-child-profile__avatar-image">
                            @else
                                <span>{{ strtoupper(substr($child->name ?? '?', 0, 1)) }}</span>
                            @endif
                        </div>
                        <span class="badge badge-green">{{ $child->statusLabel() }}</span>
                    </div>

                    <div class="portal-child-profile__body">
                        <div class="portal-child-profile__header">
                            <h3 class="portal-child-profile__name">{{ $child->name }}</h3>
                            <p class="portal-child-profile__meta">NIS {{ $child->nis }} · {{ $child->kelas?->name ?? '-' }}</p>
                        </div>

                        <dl class="portal-child-profile__facts">
                            <div>
                                <dt class="text-muted">Jenis Kelamin</dt>
                                <dd>{{ $child->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Tanggal Lahir</dt>
                                <dd>{{ $child->birth_date?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Tempat Lahir</dt>
                                <dd>{{ $child->birth_place ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Nama Panggilan</dt>
                                <dd>{{ $extra['nama_panggilan'] ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Golongan Darah</dt>
                                <dd>{{ $extra['golongan_darah'] ?? '-' }}</dd>
                            </div>
                            <div class="portal-child-profile__facts--full">
                                <dt class="text-muted">No. Virtual Account</dt>
                                <dd>
                                    <x-portal.vano
                                        :value="$child->virtualAccountNumber()"
                                        label=""
                                        hint="Gunakan nomor ini saat transfer pembayaran tagihan sekolah"
                                    />
                                </dd>
                            </div>
                            <div class="portal-child-profile__facts--full">
                                <dt class="text-muted">Alamat</dt>
                                <dd>{{ $child->address ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Saldo Keuangan</dt>
                                <dd class="portal-child-profile__amount">Rp {{ number_format($child->saldo_keuangan ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Saldo Cashless</dt>
                                <dd class="portal-child-profile__amount">Rp {{ number_format($child->saldo_cashless ?? 0, 0, ',', '.') }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex justify-end">
                            <button type="button"
                                    class="btn-secondary btn-sm"
                                    data-open-child-profile-modal
                                    data-child-id="{{ $child->id }}"
                                    data-child-full-name="{{ e($child->name) }}"
                                    data-child-name="{{ e($child->name) }}"
                                    data-child-gender="{{ $child->gender ?? '' }}"
                                    data-child-birth-date="{{ $child->birth_date?->format('Y-m-d') ?? '' }}"
                                    data-child-birth-place="{{ e($child->birth_place ?? '') }}"
                                    data-child-address="{{ e($child->address ?? '') }}"
                                    data-child-nama-panggilan="{{ e($extra['nama_panggilan'] ?? '') }}"
                                    data-child-golongan-darah="{{ e($extra['golongan_darah'] ?? '') }}">
                                <x-icon name="pencil" size="sm" />
                                <span>Ubah Detail</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection

@push('modals')
<x-modal id="ortu-child-profile-modal" title="Ubah Detail Anak">
    <form id="ortu-child-profile-form" data-fetch-form data-close-modal="ortu-child-profile-modal" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="siswa_id" id="ortu-child-profile-siswa-id">

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="ortu-child-profile-name" class="form-label">Nama Panjang</label>
                <input id="ortu-child-profile-name" type="text" name="name" class="form-input" maxlength="255" required>
            </div>
            <div>
                <label for="ortu-child-profile-gender" class="form-label">Jenis Kelamin</label>
                <select id="ortu-child-profile-gender" name="gender" class="form-select">
                    <option value="">- Pilih -</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
            <div>
                <label for="ortu-child-profile-birth_date" class="form-label">Tanggal Lahir</label>
                <input id="ortu-child-profile-birth_date" type="date" name="birth_date" class="form-input">
            </div>
            <div>
                <label for="ortu-child-profile-birth_place" class="form-label">Tempat Lahir</label>
                <input id="ortu-child-profile-birth_place" type="text" name="birth_place" class="form-input" maxlength="255">
            </div>
            <div>
                <label for="ortu-child-profile-nama_panggilan" class="form-label">Nama Panggilan</label>
                <input id="ortu-child-profile-nama_panggilan" type="text" name="nama_panggilan" class="form-input" maxlength="100">
            </div>
            <div>
                <label for="ortu-child-profile-golongan_darah" class="form-label">Golongan Darah</label>
                <select id="ortu-child-profile-golongan_darah" name="golongan_darah" class="form-select">
                    <option value="">- Pilih -</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="AB">AB</option>
                    <option value="O">O</option>
                    <option value="A+">A+</option>
                    <option value="B+">B+</option>
                    <option value="AB+">AB+</option>
                    <option value="O+">O+</option>
                    <option value="A-">A-</option>
                    <option value="B-">B-</option>
                    <option value="AB-">AB-</option>
                    <option value="O-">O-</option>
                </select>
            </div>
        </div>

        <div>
            <label for="ortu-child-profile-address" class="form-label">Alamat</label>
            <textarea id="ortu-child-profile-address" name="address" rows="4" class="form-textarea" maxlength="5000"></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-modal-close="ortu-child-profile-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('ortu-child-profile-modal');
    const form = document.getElementById('ortu-child-profile-form');
    const siswaIdInput = document.getElementById('ortu-child-profile-siswa-id');
    const nameInput = document.getElementById('ortu-child-profile-name');
    const genderInput = document.getElementById('ortu-child-profile-gender');
    const birthDateInput = document.getElementById('ortu-child-profile-birth_date');
    const birthPlaceInput = document.getElementById('ortu-child-profile-birth_place');
    const addressInput = document.getElementById('ortu-child-profile-address');
    const namaPanggilanInput = document.getElementById('ortu-child-profile-nama_panggilan');
    const golonganDarahInput = document.getElementById('ortu-child-profile-golongan_darah');

    document.querySelectorAll('[data-open-child-profile-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            const childId = button.getAttribute('data-child-id');
            const childFullName = button.getAttribute('data-child-full-name');
            const title = modal.querySelector('[data-default-title]') || modal.querySelector('.modal-panel__header h3');
            if (title) {
                title.textContent = 'Ubah Detail Anak — ' + (childFullName || 'Anak');
            }

            siswaIdInput.value = childId || '';
            form.action = '{{ route('portal.ortu.anak.update', ['siswa' => '__ID__']) }}'.replace('__ID__', childId || '');
            nameInput.value = button.getAttribute('data-child-full-name') || '';
            genderInput.value = button.getAttribute('data-child-gender') || '';
            birthDateInput.value = button.getAttribute('data-child-birth-date') || '';
            birthPlaceInput.value = button.getAttribute('data-child-birth-place') || '';
            addressInput.value = button.getAttribute('data-child-address') || '';
            namaPanggilanInput.value = button.getAttribute('data-child-nama-panggilan') || '';
            golonganDarahInput.value = button.getAttribute('data-child-golongan-darah') || '';
            modal.classList.remove('hidden');
        });
    });

    // Auto-update page after form submission
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        const siswaId = formData.get('siswa_id');

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.showToast(result.message, 'success');
                modal.classList.add('hidden');
                // Reload page after 500ms to ensure modal closes first
                setTimeout(() => window.location.reload(), 500);
            } else {
                window.showToast(result.message || 'Gagal menyimpan data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showToast('Terjadi kesalahan saat menyimpan data', 'error');
        });
    });
})();
</script>
@endpush
