@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="tagihan-page-config"
     data-default-spp="{{ (int) $defaultSppAmount }}"
     data-default-jenis-tagihan-id="{{ $defaultJenisTagihanId }}"
     data-default-due-date="{{ $defaultDueDate }}"
     data-default-periode="{{ $defaultPeriode }}"
     data-operator-school-id="{{ $operatorSchoolId }}"
     data-generate-preview-url="{{ route('admin.keuangan.tagihan.generate-preview') }}"></div>

<script type="application/json" id="tagihan-jenis-flat">@json($jenisTagihanFlat)</script>
<script type="application/json" id="tagihan-tahun-flat">@json($tahunAkademikFlat)</script>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="stat-card stat-card-blue">
        <p class="text-sm text-slate-500">Total Tagihan</p>
        <p class="mt-2 text-2xl font-bold">{{ $stats['total'] }}</p>
    </div>
    <div class="stat-card stat-card-green">
        <p class="text-sm text-slate-500">Lunas</p>
        <p class="mt-2 text-2xl font-bold">{{ $stats['lunas'] }}</p>
    </div>
    <div class="stat-card stat-card-amber">
        <p class="text-sm text-slate-500">Belum Lunas</p>
        <p class="mt-2 text-2xl font-bold">{{ $stats['belum_lunas'] }}</p>
    </div>
</div>

<x-admin.datatable-page
    title="Data Tagihan"
    subtitle="Kelola tagihan SPP dan biaya lainnya per siswa"
    :ajax-url="route('admin.keuangan.tagihan.data')"
    :columns="['NIS', 'No. VA', 'Nama', 'Kelas', 'Tahun Akademik', 'Jenis', 'Periode', 'Urutan', 'Bruto', 'Potongan', 'Tagihan', 'Terbayar', 'Sisa', 'Status', 'Cicilan', 'Metode', 'Tgl. Lunas', 'Jatuh Tempo', 'Aksi']"
    :column-options="[
        0 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        1 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        2 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        3 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        4 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        5 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        6 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        7 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        8 => ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => false],
        9 => ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => false],
        10 => ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => false],
        11 => ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => false],
        12 => ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => false],
        13 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => true],
        14 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => true],
        15 => ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => false],
        16 => ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => false],
        17 => ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => false],
        18 => ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ]"
    :show-export="true">
    <x-slot:actions>
        <button type="button" class="btn-secondary flex-1 sm:flex-none"
                data-open-modal="generate-spp-modal"
                data-form-reset="generate-spp-form">
            <x-icon name="files" size="sm" class="mr-1" /> Generate Tagihan
        </button>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="tagihan-modal"
                data-form-reset="tagihan-form"
                data-store-url="{{ route('admin.keuangan.tagihan.store') }}">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Tagihan
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        @if(! $operatorSchoolId)
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah_id'])
        @endif
        <div>
            <label class="form-label" for="filter-status">Status</label>
            <select name="status" id="filter-status" class="form-input">
                <option value="">Semua</option>
                <option value="0">Belum Lunas</option>
                <option value="2">Cicilan</option>
                <option value="1">Lunas</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-is-cicilan">Cicilan</label>
            <select name="is_cicilan" id="filter-is-cicilan" class="form-input">
                <option value="">Semua</option>
                <option value="1">Ya</option>
                <option value="0">Tidak</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-jenis">Jenis</label>
            <select name="jenis" id="filter-jenis" class="form-input">
                <option value="">Semua</option>
                @foreach($jenisTagihanList as $jenisTagihan)
                    <option value="{{ $jenisTagihan->name }}">{{ $jenisTagihan->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-tahun-akademik">Tahun Akademik</label>
            <select name="tahun_akademik_id" id="filter-tahun-akademik" class="form-input">
                <option value="">Semua</option>
                @foreach($tahunAkademikList as $tahun)
                    <option value="{{ $tahun->id }}">{{ $tahun->name }}{{ $tahun->is_active ? ' (Aktif)' : '' }}</option>
                @endforeach
            </select>
        </div>
        @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas_id'])
        <div>
            <label class="form-label" for="filter-periode">Periode</label>
            <input type="month" name="periode" id="filter-periode" class="form-input">
        </div>
        @include('admin.partials.filters.date-range', [
            'from' => request('date_from'),
            'to' => request('date_to'),
            'fromId' => 'filter-date-from',
            'toId' => 'filter-date-to',
            'fromLabel' => 'Jatuh Tempo Dari',
            'toLabel' => 'Jatuh Tempo Sampai',
        ])
        @include('admin.partials.filters.date-range', [
            'fromName' => 'paid_dt_from',
            'toName' => 'paid_dt_to',
            'from' => request('paid_dt_from'),
            'to' => request('paid_dt_to'),
            'fromId' => 'filter-paid_dt-from',
            'toId' => 'filter-paid_dt-to',
            'fromLabel' => 'Tgl Bayar Dari',
            'toLabel' => 'Tgl Bayar Sampai',
        ])
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="tagihan-modal" title="Tambah Tagihan">
    <form id="tagihan-form"
          data-fetch-form
          data-default-action="{{ route('admin.keuangan.tagihan.store') }}"
          data-reload-table
          data-close-modal="tagihan-modal"
          action="{{ route('admin.keuangan.tagihan.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Tagihan</h4>
            <div class="form-section__body space-y-4">
                <x-siswa-select />
                <div>
                    <label class="form-label" for="tagihan-tahun-akademik">Tahun Akademik</label>
                    <select name="tahun_akademik_id" id="tagihan-tahun-akademik" class="form-input" required>
                        @forelse($tahunAkademikList as $tahun)
                            <option value="{{ $tahun->id }}" @selected($tahun->is_active)>{{ $tahun->name }}{{ $tahun->is_active ? ' (Aktif)' : '' }}</option>
                        @empty
                            <option value="" disabled selected>Belum ada tahun akademik</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="form-label" for="tagihan-jenis">Jenis Tagihan</label>
                    <select name="jenis_tagihan_id" id="tagihan-jenis" class="form-input" required>
                        @forelse($jenisTagihanList as $jenisTagihan)
                            <option value="{{ $jenisTagihan->id }}"
                                    data-default-amount="{{ (int) ($jenisTagihan->default_amount ?? 0) }}"
                                    data-is-spp="{{ $jenisTagihan->is_spp ? '1' : '0' }}"
                                    @selected($jenisTagihan->id === $defaultJenisTagihanId)>
                                {{ $jenisTagihan->name }}
                            </option>
                        @empty
                            <option value="" disabled selected>Belum ada jenis tagihan</option>
                        @endforelse
                    </select>
                    <p class="text-muted mt-1 text-xs">Tahun akademik dan jenis tagihan bersifat universal (berlaku untuk semua sekolah).</p>
                    @if($jenisTagihanList->isEmpty())
                        <p class="mt-1 text-xs text-amber-600">
                            <a href="{{ route('admin.master-data.jenis-tagihan.index') }}" class="underline">Kelola jenis tagihan</a>
                            terlebih dahulu.
                        </p>
                    @endif
                </div>
                <div>
                    <label class="form-label" for="tagihan-amount">Nominal (Rp)</label>
                    <x-form.amount name="amount" id="tagihan-amount" :value="$defaultSppAmount" :min="0" required />
                </div>
                <div>
                    <label class="form-label" for="tagihan-periode">Periode</label>
                    <input type="month" name="periode" id="tagihan-periode" class="form-input" value="{{ $defaultPeriode }}">
                    <p class="mt-1 text-xs text-slate-500">
                        Untuk jenis <strong>SPP {BULAN}</strong>, periode otomatis mengikuti nama jenis. Jenis lain boleh dikosongkan (otomatis bulan saat dibuat).
                    </p>
                </div>
                <div>
                    <label class="form-label" for="tagihan-due-date">Jatuh Tempo</label>
                    <input type="date" name="due_date" id="tagihan-due-date" class="form-input" value="{{ $defaultDueDate }}">
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="tagihan-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan Tagihan
            </button>
        </div>
    </form>
</x-modal>

<x-modal id="generate-spp-modal" title="Generate Tagihan Massal">
    <form id="generate-spp-form"
          data-fetch-form
          data-reload-table
          data-close-modal="generate-spp-modal"
          data-default-action="{{ route('admin.keuangan.tagihan.generate') }}"
          action="{{ route('admin.keuangan.tagihan.generate') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Pengaturan Generate</h4>
            <p class="form-section__desc">Buat tagihan untuk siswa aktif pada tahun akademik dan periode terpilih. Siswa yang sudah memiliki tagihan dengan jenis yang sama pada kombinasi tersebut akan dilewati.</p>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="generate-tahun-akademik">Tahun Akademik</label>
                    <select name="tahun_akademik_id" id="generate-tahun-akademik" class="form-input" required>
                        @foreach($tahunAkademikList as $tahun)
                            <option value="{{ $tahun->id }}" @selected($tahun->is_active)>
                                {{ $tahun->name }}{{ $tahun->is_active ? ' (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="generate-jenis-tagihan">Jenis Tagihan</label>
                    <select name="jenis_tagihan_id" id="generate-jenis-tagihan" class="form-input" required>
                        @foreach($jenisTagihanList as $jenisTagihan)
                            <option value="{{ $jenisTagihan->id }}"
                                    data-default-amount="{{ (int) ($jenisTagihan->default_amount ?? 0) }}">
                                {{ $jenisTagihan->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @include('admin.partials.filters.class-select', [
                    'classes' => $classes,
                    'name' => 'kelas_id',
                    'id' => 'generate-kelas_id',
                    'label' => 'Kelas',
                ])
                <div>
                    <label class="form-label" for="generate-periode">Periode</label>
                    <input type="month" name="periode" id="generate-periode" class="form-input" value="{{ $defaultPeriode }}">
                    <p class="mt-1 text-xs text-slate-500">Untuk jenis <strong>SPP {BULAN}</strong>, periode otomatis mengikuti bulan pada nama jenis dan tahun akademik terpilih.</p>
                </div>
                <div id="generate-spp-preview" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                    Pilih tahun akademik, kelas, dan periode untuk melihat ringkasan generate.
                </div>
                <div>
                    <label class="form-label" for="generate-amount">Nominal Tagihan (Rp)</label>
                    <x-form.amount name="amount" id="generate-amount" :value="$defaultSppAmount" :min="0" placeholder="Default dari jenis tagihan terpilih" />
                </div>
                <div>
                    <label class="form-label" for="generate-due-date">Jatuh Tempo</label>
                    <input type="date" name="due_date" id="generate-due-date" class="form-input" value="{{ $defaultDueDate }}">
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="generate-spp-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="files" size="sm" class="mr-1" /> Generate Tagihan
            </button>
        </div>
    </form>
</x-modal>

<x-modal id="tagihan-edit-modal" title="Ubah Tagihan">
    <form id="tagihan-edit-form"
          data-fetch-form
          data-reload-table
          data-close-modal="tagihan-edit-modal"
          action="#"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Tagihan</h4>
            <div class="form-section__body">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                    <p class="text-slate-500">Siswa</p>
                    <p id="tagihan-edit-siswa" class="font-medium text-slate-900 dark:text-white">-</p>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        <div>
                            <p class="text-slate-500">Jenis</p>
                            <p id="tagihan-edit-jenis" class="font-medium text-slate-900 dark:text-white">-</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Tahun Akademik</p>
                            <p id="tagihan-edit-tahun" class="font-medium text-slate-900 dark:text-white">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Pengaturan Tagihan</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="tagihan-edit-amount">Nominal (Rp)</label>
                    <x-form.amount name="amount" id="tagihan-edit-amount" :min="0" required />
                </div>
                <div>
                    <label class="form-label" for="tagihan-edit-periode">Periode</label>
                    <input type="month" name="periode" id="tagihan-edit-periode" class="form-input">
                    <p class="mt-1 text-xs text-slate-500">
                        Untuk jenis <strong>SPP {BULAN}</strong>, periode akan disesuaikan otomatis mengikuti bulan jenis dan tahun akademik tagihan.
                    </p>
                </div>
                <div>
                    <label class="form-label" for="tagihan-edit-urutan">Urutan</label>
                    <input type="number" name="urutan" id="tagihan-edit-urutan" class="form-input" min="0" step="1" placeholder="Opsional">
                    <p class="mt-1 text-xs text-slate-500">Urutan tampilan tagihan (opsional).</p>
                </div>
                <div>
                    <label class="form-label" for="tagihan-edit-due-date">Jatuh Tempo</label>
                    <input type="date" name="due_date" id="tagihan-edit-due-date" class="form-input">
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Pembayaran Cicilan</h4>
            <div class="form-section__body space-y-4">
                <x-form.checkbox name="enable_cicilan" id="tagihan-edit-enable-cicilan" label="Aktifkan cicilan" />
                <p id="tagihan-edit-cicilan-lock-note" class="hidden text-xs text-amber-700 dark:text-amber-300">Cicilan yang sudah berjalan tidak dapat dibatalkan.</p>
                <p class="text-muted text-xs">Tagihan cicilan dapat dibayar bertahap. Setiap pembayaran memotong sisa tagihan dan mencatat baris cicilan lunas.</p>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="tagihan-edit-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan Perubahan
            </button>
        </div>
    </form>
</x-modal>

<x-modal id="tagihan-cicilan-view-modal" title="Riwayat Cicilan">
    <div class="space-y-4">
        <p id="tagihan-cicilan-view-summary" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">-</p>
        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3">Ke-</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-right">Terbayar</th>
                        <th class="px-4 py-3">Tgl. Bayar</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody id="tagihan-cicilan-view-body"></tbody>
            </table>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button"
                    id="tagihan-cicilan-cancel-btn"
                    class="btn-danger hidden"
                    data-confirm-title="Batalkan Cicilan"
                    data-confirm-message="Fitur cicilan pada tagihan ini akan dinonaktifkan."
                    data-confirm-detail="Hanya bisa dibatalkan jika belum ada pembayaran cicilan."
                    data-confirm-text="Ya, Batalkan"
                    data-confirm-tone="warning"
                    data-confirm-icon="ti-ban"
                    data-confirm-header-icon="ti-ban"
                    data-confirm-footnote="Tagihan kembali ke pembayaran penuh (bukan cicilan).">
                Batalkan Cicilan
            </button>
            <button type="button" data-modal-close="tagihan-cicilan-view-modal" class="btn-secondary">Tutup</button>
        </div>
    </div>
</x-modal>

<x-modal id="tagihan-potongan-modal" title="Detail Potongan Tagihan" size="lg">
    <div id="tagihan-potongan-body" class="space-y-4 text-sm"></div>
    <div class="mt-4 flex justify-end border-t border-slate-200 pt-4 dark:border-slate-800">
        <button type="button" data-modal-close="tagihan-potongan-modal" class="btn-secondary">Tutup</button>
    </div>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/tagihan-form.js') }}?v=11"></script>
<script src="{{ asset('js/tagihan-cicilan.js') }}?v=4"></script>
<script src="{{ asset('js/tagihan-potongan.js') }}?v=1"></script>
@endpush
