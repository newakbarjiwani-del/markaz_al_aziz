@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <p class="text-sm text-slate-500 dark:text-slate-400">Referensi komponen UI — gunakan class dan pola yang sama di seluruh modul.</p>
</div>

<div class="flex flex-col gap-8 lg:flex-row lg:items-start">
    @php
        $uiKitNavItems = [
            'colors' => 'Warna',
            'typography' => 'Tipografi',
            'buttons' => 'Tombol',
            'dropdown' => 'Dropdown',
            'forms' => 'Form',
            'select2' => 'Select2',
            'datepicker' => 'Datepicker',
            'daterange' => 'Date Range',
            'periode' => 'Periode',
            'badges' => 'Badge',
            'alerts' => 'Alert',
            'cards' => 'Card',
            'widgets' => 'Widget',
            'tables' => 'Tabel',
            'datatable' => 'DataTable',
            'charts' => 'Chart',
            'modal' => 'Modal',
            'toast' => 'Toast',
        ];
    @endphp

    <nav class="ui-kit-nav card shrink-0 p-3 lg:sticky lg:top-20 lg:w-48" aria-label="Navigasi komponen UI">
        <button type="button"
                class="ui-kit-nav__toggle lg:hidden"
                aria-expanded="false"
                aria-controls="ui-kit-nav-panel">
            <span class="min-w-0 flex-1 text-left">
                <span class="ui-kit-nav__toggle-title block text-sm font-semibold text-slate-800 dark:text-slate-100">Navigasi Komponen</span>
                <span class="ui-kit-nav__toggle-active text-muted mt-0.5 block truncate text-xs">Warna</span>
            </span>
            <x-icon name="chevron-down" class="ui-kit-nav__toggle-icon shrink-0" size="sm" />
        </button>

        <p class="ui-kit-nav__heading mb-2 hidden px-2 text-xs font-semibold uppercase tracking-wider text-slate-400 lg:block">Komponen</p>

        <div id="ui-kit-nav-panel" class="ui-kit-nav__panel">
            <div class="ui-kit-nav__panel-inner">
                @foreach($uiKitNavItems as $id => $label)
                    <a href="#{{ $id }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </nav>

    <div class="min-w-0 flex-1 space-y-10">
        {{-- Colors --}}
        <x-ui-section id="colors" title="Warna Brand" description="Palette dari logo {{ config('app.name') }}.">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['Primary 600', 'var(--color-primary-600)', '#436137', 'Tombol utama, link'],
                    ['Primary 100', 'var(--color-primary-100)', '#e6ede4', 'Background aktif'],
                    ['Accent 500', 'var(--color-accent-500)', '#c0a830', 'Highlight, peringatan'],
                    ['Accent 100', 'var(--color-accent-100)', '#f5eed4', 'Background accent'],
                ] as [$name, $var, $hex, $use])
                    <div class="color-swatch">
                        <div class="swatch-block" style="background: {{ $var }}"></div>
                        <p class="font-medium text-slate-700 dark:text-slate-200">{{ $name }}</p>
                        <p class="text-slate-500">{{ $hex }}</p>
                        <p class="mt-1 text-slate-400">{{ $use }}</p>
                    </div>
                @endforeach
            </div>
        </x-ui-section>

        {{-- Typography --}}
        <x-ui-section id="typography" title="Tipografi" description="Plus Jakarta Sans — font utama aplikasi.">
            <div class="ui-preview space-y-4">
                <p class="text-3xl font-bold text-slate-900 dark:text-white">Heading 1 — Dashboard</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">Heading 2 — Section Title</p>
                <p class="text-lg font-semibold text-slate-900 dark:text-white">Heading 3 — Card Title</p>
                <p class="text-sm text-slate-600 dark:text-slate-300">Body — Paragraf dan konten umum. Lorem ipsum dolor sit amet.</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Caption — Label kecil, hint, metadata</p>
                <p class="font-mono text-sm text-primary-700 dark:text-primary-400">NIS-2025-0042 — Monospace data</p>
            </div>
        </x-ui-section>

        {{-- Buttons --}}
        <x-ui-section id="buttons" title="Tombol" description="Varian tombol standar aplikasi.">
            <div class="ui-preview flex flex-wrap gap-3">
                <button type="button" class="btn-primary"><x-icon name="plus" size="sm" /> Primary</button>
                <button type="button" class="btn-accent"><x-icon name="coin" size="sm" /> Accent</button>
                <button type="button" class="btn-secondary"><x-icon name="download" size="sm" /> Secondary</button>
                <button type="button" class="btn-danger"><x-icon name="trash" size="sm" /> Danger</button>
                <button type="button" class="btn-primary" disabled>Disabled</button>
            </div>
            <div class="ui-preview mt-3 flex flex-wrap items-center gap-3">
                <button type="button" class="btn-primary btn-sm">Small</button>
                <button type="button" class="btn-primary">Default</button>
                <button type="button" class="btn-primary btn-lg">Large</button>
            </div>
            <div class="ui-preview mt-3">
                <p class="mb-3 text-sm text-slate-500">Aksi tabel (edit / hapus)</p>
                <div class="action-group">
                    <button type="button" class="btn-action btn-action-edit" title="Edit"><x-icon name="pencil" size="sm" /><span class="btn-action-label">Edit</span></button>
                    <button type="button" class="btn-action btn-action-delete" title="Hapus"><x-icon name="trash" size="sm" /><span class="btn-action-label">Hapus</span></button>
                    <button type="button" class="btn-action btn-action-view" title="Lihat"><x-icon name="eye" size="sm" /><span class="btn-action-label">Detail</span></button>
                </div>
            </div>
            <div class="ui-preview mt-3">
                <p class="mb-3 text-sm text-slate-500">Aksi perpustakaan (perpanjang / kembalikan)</p>
                <div class="action-group">
                    <button type="button" class="btn-action btn-action-view" title="Detail"><x-icon name="eye" size="sm" /><span class="btn-action-label">Detail</span></button>
                    <button type="button" class="btn-action btn-action--extend" title="Perpanjang"><x-icon name="calendar-plus" size="sm" /><span class="btn-action-label">Perpanjang</span></button>
                    <button type="button" class="btn-action btn-action--return" title="Kembalikan"><x-icon name="book-upload" size="sm" /><span class="btn-action-label">Kembalikan</span></button>
                </div>
            </div>
        </x-ui-section>

        {{-- Dropdown button --}}
        <x-ui-section id="dropdown" title="Dropdown Button" description="Tombol dengan menu aksi — klik di luar atau Escape untuk menutup.">
            <div class="ui-preview flex flex-wrap items-center gap-3">
                <x-dropdown-button label="Aksi" variant="primary" menu-label="Menu aksi">
                    <x-dropdown-button.item icon="download">Unduh Excel</x-dropdown-button.item>
                    <x-dropdown-button.item icon="file-type-pdf">Export PDF</x-dropdown-button.item>
                    <x-dropdown-button.divider />
                    <x-dropdown-button.item icon="settings">Pengaturan</x-dropdown-button.item>
                </x-dropdown-button>

                <x-dropdown-button label="Secondary" icon="filter" variant="secondary">
                    <x-dropdown-button.heading>Filter cepat</x-dropdown-button.heading>
                    <x-dropdown-button.item icon="check">Aktif saja</x-dropdown-button.item>
                    <x-dropdown-button.item icon="clock">Menunggu</x-dropdown-button.item>
                    <x-dropdown-button.item icon="x">Nonaktif</x-dropdown-button.item>
                </x-dropdown-button>

                <x-dropdown-button label="Accent" variant="accent" size="sm">
                    <x-dropdown-button.item icon="coin" tone="accent">Bayar tunai</x-dropdown-button.item>
                    <x-dropdown-button.item icon="credit-card">Virtual account</x-dropdown-button.item>
                </x-dropdown-button>

                <x-dropdown-button label="Hapus" variant="danger" size="sm" align="start">
                    <x-dropdown-button.item icon="archive">Arsipkan</x-dropdown-button.item>
                    <x-dropdown-button.item icon="trash" tone="danger">Hapus permanen</x-dropdown-button.item>
                </x-dropdown-button>

                <x-dropdown-button label="Split" variant="primary" :split="true" icon="plus" menu-label="Opsi tambahan">
                    <x-dropdown-button.item icon="user-plus">Tambah satu</x-dropdown-button.item>
                    <x-dropdown-button.item icon="upload">Import Excel</x-dropdown-button.item>
                </x-dropdown-button>

                <x-dropdown-button label="Disabled" variant="secondary" :disabled="true">
                    <x-dropdown-button.item>Tidak tampil</x-dropdown-button.item>
                </x-dropdown-button>
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                Komponen: <code class="font-mono">&lt;x-dropdown-button&gt;</code>,
                <code class="font-mono">&lt;x-dropdown-button.item&gt;</code>,
                <code class="font-mono">&lt;x-dropdown-button.divider&gt;</code>,
                <code class="font-mono">&lt;x-dropdown-button.heading&gt;</code>
                — props: <code class="font-mono">variant</code> (primary|secondary|accent|danger),
                <code class="font-mono">size</code> (sm|lg), <code class="font-mono">align</code> (start|end), <code class="font-mono">split</code>.
            </p>
        </x-ui-section>

        {{-- Forms --}}
        <x-ui-section id="forms" title="Form" description="Input, select, checkbox, radio, date, dan nominal terformat.">
            <div class="ui-preview grid gap-4 md:grid-cols-2">
                <div>
                    <label class="form-label">Text Input</label>
                    <input type="text" class="form-input" placeholder="Masukkan teks...">
                </div>
                <div>
                    <label class="form-label">Select</label>
                    <select class="form-select">
                        <option value="">Pilih kelas</option>
                        <option>X IPA 1</option>
                        <option>XI IPS 1</option>
                        <option>XII IPA 1</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Nominal (formattedNumber)</label>
                    <input type="text" class="form-input formattedNumber" inputmode="numeric" placeholder="0" value="1.500.000">
                </div>
                <div>
                    <label class="form-label" for="ui-native-date">Date (native)</label>
                    <input type="date" id="ui-native-date" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="ui-native-month">Month (native)</label>
                    <input type="month" id="ui-native-month" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="ui-native-datetime">Date & Time (native)</label>
                    <input type="datetime-local" id="ui-native-datetime" class="form-input">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Textarea</label>
                    <textarea class="form-input" rows="3" placeholder="Catatan..."></textarea>
                </div>
                <div class="flex flex-wrap gap-6 md:col-span-2">
                    <x-form.checkbox label="Checkbox" :checked="true" :hidden-fallback="false" />
                    <label class="form-check">
                        <input type="radio" name="demo-radio" class="form-radio" checked>
                        <span>Radio A</span>
                    </label>
                    <label class="form-check">
                        <input type="radio" name="demo-radio" class="form-radio">
                        <span>Radio B</span>
                    </label>
                </div>
            </div>
        </x-ui-section>

        {{-- Select2 --}}
        <x-ui-section id="select2" title="Select2" description="Lookup select bergaya form aplikasi (offline & AJAX). Style di app.css — tanpa Select2 CDN CSS.">
            <div class="ui-preview grid gap-4 md:grid-cols-2">
                <div>
                    <label class="form-label" for="ui-s2-offline">Select2 offline <span class="ui-kit-code">data-s2</span></label>
                    <select id="ui-s2-offline"
                            class="form-input"
                            data-s2
                            data-placeholder="Pilih kelas..."
                            data-allow-clear="1">
                        <option value=""></option>
                        <option value="1">X IPA 1</option>
                        <option value="2">X IPA 2</option>
                        <option value="3">XI IPS 1</option>
                        <option value="4">XII IPA 1</option>
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Opsi tetap di HTML — cocok untuk master kecil (status, kelas terbatas).</p>
                </div>
                <div>
                    <label class="form-label" for="ui-s2-offline-pre">Dengan nilai terpilih</label>
                    <select id="ui-s2-offline-pre"
                            class="form-input"
                            data-s2
                            data-placeholder="Pilih status..."
                            data-allow-clear="1">
                        <option value=""></option>
                        <option value="1" selected>Aktif</option>
                        <option value="0">Nonaktif</option>
                        <option value="2">Menunggu</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="ui-s2-multiple">Select2 multiple <span class="ui-kit-code">multiple</span> + <span class="ui-kit-code">data-s2</span></label>
                    <select id="ui-s2-multiple"
                            class="form-input"
                            name="ui_kit_kelas_ids[]"
                            multiple
                            data-s2
                            data-placeholder="Pilih satu atau lebih kelas..."
                            data-allow-clear="1">
                        <option value="1" selected>X IPA 1</option>
                        <option value="2" selected>X IPA 2</option>
                        <option value="3">XI IPS 1</option>
                        <option value="4">XI IPA 1</option>
                        <option value="5">XII IPA 1</option>
                        <option value="6">XII IPS 1</option>
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Chip pilihan mengikuti warna brand; dropdown tetap terbuka saat menambah opsi.</p>
                </div>
                <div class="md:col-span-2">
                    <x-siswa-select
                        id="ui-s2-ajax-siswa"
                        name="ui_kit_siswa_id"
                        label="Select2 AJAX (siswa)"
                        :required="false"
                        :status="null"
                        placeholder="Cari nama atau NIS (min. 3 karakter)"
                    />
                    <p class="mt-1.5 text-xs text-slate-500">
                        Pakai <span class="ui-kit-code">&lt;x-siswa-select /&gt;</span> / <span class="ui-kit-code">data-ajax-select</span> —
                        pencarian setelah 3 karakter, response <span class="ui-kit-code">AjaxSelect</span>.
                    </p>
                </div>
            </div>
            <div class="ui-preview mt-4 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                <p class="font-semibold text-slate-800 dark:text-slate-100">Pola inisialisasi</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                    <li><span class="ui-kit-code">initOfflineSelect2s()</span> — select lokal <span class="ui-kit-code">[data-s2]</span> (single & multiple)</li>
                    <li><span class="ui-kit-code">initAjaxSelects()</span> — lookup AJAX <span class="ui-kit-code">[data-ajax-select]</span></li>
                    <li>Select2 4.0.13 — jangan pakai opsi 4.1-only (<span class="ui-kit-code">containerCssClass</span>, dll.)</li>
                </ul>
            </div>
        </x-ui-section>

        {{-- Datepicker --}}
        <x-ui-section id="datepicker" title="Datepicker" description="Flatpickr bertema form aplikasi. Shortcut (Hari ini / Hapus) di kanan kalender.">
            <div class="ui-preview grid gap-4 md:grid-cols-2">
                <div class="datepicker-field">
                    <label class="form-label" for="ui-datepicker">Tanggal tunggal</label>
                    <input type="text"
                           id="ui-datepicker"
                           class="form-input datepicker"
                           data-datepicker
                           placeholder="Pilih tanggal..."
                           autocomplete="off">
                    <p class="mt-1.5 text-xs text-slate-500">Class <span class="ui-kit-code">datepicker</span> + <span class="ui-kit-code">data-datepicker</span></p>
                </div>
                <div class="datepicker-field">
                    <label class="form-label" for="ui-datepicker-time">Tanggal & waktu</label>
                    <input type="text"
                           id="ui-datepicker-time"
                           class="form-input datepicker"
                           data-datepicker
                           data-enable-time="1"
                           placeholder="Pilih tanggal & waktu..."
                           autocomplete="off">
                    <p class="mt-1.5 text-xs text-slate-500"><span class="ui-kit-code">data-enable-time="1"</span></p>
                </div>
                <div class="datepicker-field md:col-span-2">
                    <label class="form-label" for="ui-datepicker-prefill">Dengan nilai awal</label>
                    <input type="text"
                           id="ui-datepicker-prefill"
                           class="form-input datepicker"
                           data-datepicker
                           value="{{ now()->toDateString() }}"
                           placeholder="Pilih tanggal..."
                           autocomplete="off">
                </div>
            </div>
            <div class="ui-preview mt-4 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                <p class="font-semibold text-slate-800 dark:text-slate-100">Rollout berikutnya</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                    <li>Muat Flatpickr + <span class="ui-kit-code">public/js/datepicker.js</span> di halaman yang butuh picker</li>
                    <li>Ganti <span class="ui-kit-code">input type="date"</span> filter/form dengan markup di atas</li>
                    <li>Tema calendar mengikuti token brand (primary / accent / dark mode)</li>
                </ul>
            </div>
        </x-ui-section>

        {{-- Date range --}}
        <x-ui-section id="daterange" title="Date Range" description="Satu input rentang → menulis pasangan hidden date_from / date_to. Shortcut di kanan: Hari ini, Minggu ini, Bulan ini.">
            <div class="ui-preview grid gap-4 md:grid-cols-2">
                <div class="daterange-field md:col-span-2">
                    <label class="form-label" for="ui-daterange">Rentang tanggal</label>
                    <input type="text"
                           id="ui-daterange"
                           class="form-input daterange"
                           data-daterange
                           data-date-from="#ui-daterange-from"
                           data-date-to="#ui-daterange-to"
                           placeholder="Pilih rentang tanggal..."
                           autocomplete="off">
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label" for="ui-daterange-from">Hidden <span class="ui-kit-code">date_from</span></label>
                            <input type="text" id="ui-daterange-from" class="form-input font-mono text-xs" readonly placeholder="Y-m-d">
                        </div>
                        <div>
                            <label class="form-label" for="ui-daterange-to">Hidden <span class="ui-kit-code">date_to</span></label>
                            <input type="text" id="ui-daterange-to" class="form-input font-mono text-xs" readonly placeholder="Y-m-d">
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-3 text-sm font-medium text-slate-700 dark:text-slate-300">Native pair (filter saat ini)</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @include('admin.partials.filters.date-range', [
                            'from' => null,
                            'to' => null,
                            'fromName' => 'ui_native_from',
                            'toName' => 'ui_native_to',
                            'fromId' => 'ui-native-from',
                            'toId' => 'ui-native-to',
                        ])
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Partial <span class="ui-kit-code">admin.partials.filters.date-range</span> — masih native; diganti bertahap ke <span class="ui-kit-code">data-daterange</span>.</p>
                </div>
            </div>
            <div class="ui-preview mt-4 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                <p class="font-semibold text-slate-800 dark:text-slate-100">Pola markup</p>
                <pre class="mt-2 overflow-x-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-900"><code>&lt;input class="form-input daterange" data-daterange
       data-date-from="#filter-date-from"
       data-date-to="#filter-date-to"&gt;
&lt;input type="hidden" id="filter-date-from" name="date_from"&gt;
&lt;input type="hidden" id="filter-date-to" name="date_to"&gt;</code></pre>
            </div>
        </x-ui-section>

        {{-- Periode --}}
        <x-ui-section id="periode" title="Periode (Bulan & Tahun)" description="Pilih bulan + tahun → kode periode 6 digit (YYYYMM / MMYYYY). Flatpickr monthSelect.">
            <div class="ui-preview grid gap-4 md:grid-cols-2">
                <div class="periodpicker-field">
                    <label class="form-label" for="ui-period-ym">Periode <span class="ui-kit-code">YYYYMM</span></label>
                    <input type="text"
                           id="ui-period-ym"
                           class="form-input periodpicker"
                           data-periodpicker
                           data-period-format="Ym"
                           data-period-alt-format="F Y"
                           data-period-output="#ui-period-ym-raw"
                           placeholder="Pilih bulan & tahun..."
                           autocomplete="off">
                    <div class="mt-3">
                        <label class="form-label" for="ui-period-ym-raw">Hasil <span class="ui-kit-code">YYYYMM</span></label>
                        <input type="text" id="ui-period-ym-raw" class="form-input font-mono text-xs" readonly placeholder="202609">
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Tampilan <span class="ui-kit-code">F Y</span> (September 2026) → nilai mentah <span class="ui-kit-code">202609</span>.</p>
                </div>
                <div class="periodpicker-field">
                    <label class="form-label" for="ui-period-my">Periode <span class="ui-kit-code">MMYYYY</span></label>
                    <input type="text"
                           id="ui-period-my"
                           class="form-input periodpicker"
                           data-periodpicker
                           data-period-format="mY"
                           data-period-alt-format="F Y"
                           data-period-output="#ui-period-my-raw"
                           placeholder="Pilih bulan & tahun..."
                           autocomplete="off">
                    <div class="mt-3">
                        <label class="form-label" for="ui-period-my-raw">Hasil <span class="ui-kit-code">MMYYYY</span></label>
                        <input type="text" id="ui-period-my-raw" class="form-input font-mono text-xs" readonly placeholder="092026">
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Tampilan <span class="ui-kit-code">F Y</span> (September 2026) → nilai mentah <span class="ui-kit-code">092026</span>.</p>
                </div>
                <div class="periodpicker-field md:col-span-2">
                    <label class="form-label" for="ui-period-raw">Tanpa alt input — nilai mentah langsung di input</label>
                    <input type="text"
                           id="ui-period-raw"
                           class="form-input periodpicker"
                           data-periodpicker
                           data-period-format="Ym"
                           data-alt-input="0"
                           placeholder="Pilih bulan & tahun..."
                           autocomplete="off">
                    <p class="mt-1.5 text-xs text-slate-500"><span class="ui-kit-code">data-alt-input="0"</span> — input menyimpan <span class="ui-kit-code">202609</span> langsung.</p>
                </div>
            </div>
            <div class="ui-preview mt-4 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                <p class="font-semibold text-slate-800 dark:text-slate-100">Pola markup</p>
                <pre class="mt-2 overflow-x-auto rounded-md bg-slate-100 p-3 text-xs dark:bg-slate-900"><code>&lt;input class="form-input periodpicker" data-periodpicker
       data-period-format="Ym"        &lt;!-- YYYYMM (default) | mY = MMYYYY --&gt;
       data-period-alt-format="F Y"   &lt;!-- tampilan alt input --&gt;
       data-period-output="#raw"      &lt;!-- opsional: tulis kode mentah ke field --&gt;
       data-alt-input="0"&gt;           &lt;!-- opsional: nilai mentah langsung di input --&gt;</code></pre>
            </div>
        </x-ui-section>

        {{-- Badges --}}
        <x-ui-section id="badges" title="Badge" description="Status label dan tag.">
            <div class="ui-preview flex flex-wrap gap-2">
                <span class="badge badge-primary">Aktif</span>
                <span class="badge badge-accent">SPP</span>
                <span class="badge badge-success">Lunas</span>
                <span class="badge badge-warning">Pending</span>
                <span class="badge badge-danger">Nonaktif</span>
                <span class="badge badge-neutral">Draft</span>
            </div>
        </x-ui-section>

        {{-- Alerts --}}
        <x-ui-section id="alerts" title="Alert" description="Pesan inline untuk feedback.">
            <div class="space-y-3">
                <div class="alert alert-info"><x-icon name="info-circle" size="md" class="shrink-0" /> Informasi — data berhasil disimpan.</div>
                <div class="alert alert-success"><x-icon name="circle-check" size="md" class="shrink-0" /> Sukses — pembayaran dikonfirmasi.</div>
                <div class="alert alert-warning"><x-icon name="alert-triangle" size="md" class="shrink-0" /> Peringatan — tagihan jatuh tempo 3 hari lagi.</div>
                <div class="alert alert-danger"><x-icon name="alert-circle" size="md" class="shrink-0" /> Error — gagal memproses permintaan.</div>
            </div>
        </x-ui-section>

        {{-- Cards --}}
        <x-ui-section id="cards" title="Card" description="Container konten utama.">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="card p-5">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Card Default</h3>
                    <p class="mt-2 text-sm text-slate-500">Border halus, radius 12px, shadow minimal.</p>
                </div>
                <div class="card border-primary-200 bg-primary-50/50 p-5 dark:border-primary-800 dark:bg-primary-950/30">
                    <h3 class="font-semibold text-primary-800 dark:text-primary-200">Card Highlight</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Varian dengan tint brand.</p>
                </div>
            </div>
        </x-ui-section>

        {{-- Widgets --}}
        <x-ui-section id="widgets" title="Widget / Stat Card" description="Komponen ringkasan dashboard.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Total Siswa" :value="248" accent="primary" />
                <x-stat-card label="Tagihan Belum Lunas" :value="32" accent="accent" />
                <x-stat-card label="Hadir Hari Ini" :value="215" accent="green" />
                <x-stat-card label="Alpha" :value="5" accent="red" />
            </div>
            <div class="card mt-4 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Saldo Dompet (contoh widget)</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">Rp 1.250.000</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-accent-100 text-accent-700 dark:bg-accent-950 dark:text-accent-300">
                        <x-icon name="wallet" size="lg" />
                    </div>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full w-3/4 rounded-full bg-primary-600"></div>
                </div>
                <p class="mt-1 text-xs text-slate-500">75% dari limit bulanan</p>
            </div>
        </x-ui-section>

        {{-- Tables --}}
        <x-ui-section id="tables" title="Tabel" description="Tabel statis dan pola DataTables.">
            @php
                $demoRows = [
                    ['2025001', 'Ahmad Fauzi', 'X IPA 1', 'aktif'],
                    ['2025002', 'Siti Nurhaliza', 'X IPA 2', 'aktif'],
                    ['2025003', 'Budi Santoso', 'XI IPS 1', 'pending'],
                ];
            @endphp
            <div class="card overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NIS</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kelas</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($demoRows as $row)
                            @php [$nis, $nama, $kelas, $status] = $row; @endphp
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-4 py-3 font-mono">{{ $nis }}</td>
                                <td class="px-4 py-3 font-medium">{{ $nama }}</td>
                                <td class="px-4 py-3">{{ $kelas }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="badge badge-{{ $status === 'aktif' ? 'success' : 'warning' }}">{{ ucfirst($status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="action-group justify-center">
                                        <button type="button" class="btn-action btn-action-edit" title="Edit">
                                            <x-icon name="pencil" size="sm" />
                                            <span class="btn-action-label">Edit</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-slate-500">Tabel HTML statis. Untuk listing besar, pakai DataTables di bawah.</p>
        </x-ui-section>

        {{-- DataTable --}}
        @php
            $dtDemoRows = [
                ['nis' => '2025001', 'nama' => 'Ahmad Fauzi', 'kelas' => 'X IPA 1', 'status' => 'aktif'],
                ['nis' => '2025002', 'nama' => 'Siti Nurhaliza', 'kelas' => 'X IPA 2', 'status' => 'aktif'],
                ['nis' => '2025003', 'nama' => 'Budi Santoso', 'kelas' => 'XI IPS 1', 'status' => 'pending'],
                ['nis' => '2025004', 'nama' => 'Dewi Lestari', 'kelas' => 'X IPA 1', 'status' => 'aktif'],
                ['nis' => '2025005', 'nama' => 'Eko Prasetyo', 'kelas' => 'XI IPA 2', 'status' => 'nonaktif'],
                ['nis' => '2025006', 'nama' => 'Fitri Handayani', 'kelas' => 'X IPA 2', 'status' => 'aktif'],
                ['nis' => '2025007', 'nama' => 'Gilang Ramadhan', 'kelas' => 'XI IPS 1', 'status' => 'pending'],
                ['nis' => '2025008', 'nama' => 'Hana Safira', 'kelas' => 'X IPA 1', 'status' => 'aktif'],
            ];
        @endphp
        <x-ui-section id="datatable" title="DataTable" description="Contoh client-side DataTables 2.x (chrome aplikasi). Modul produksi biasanya server-side via data-ajax-url.">
            <div class="space-y-8">
                {{-- Regular --}}
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-slate-800 dark:text-slate-100">Regular</h3>
                    <p class="mb-3 text-xs text-slate-500">Search, page length, sort, paging — tanpa seleksi baris.</p>
                    <div class="card card--datatable overflow-hidden">
                        <div class="card--datatable__body p-4">
                            <table id="ui-dt-regular" class="datatable-main w-full display" style="--table-min-width: 32rem">
                                <thead>
                                    <tr>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Select single row --}}
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-slate-800 dark:text-slate-100">Select row (single)</h3>
                    <p class="mb-3 text-xs text-slate-500">Checkbox per baris — hanya satu yang terpilih (klik lagi untuk batal).</p>
                    <div class="card card--datatable overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                Terpilih: <span id="ui-dt-select-label" class="font-medium text-slate-900 dark:text-slate-100">—</span>
                            </p>
                        </div>
                        <div class="card--datatable__body p-4">
                            <table id="ui-dt-select" class="datatable-main w-full display ui-dt--selectable" style="--table-min-width: 36rem">
                                <thead>
                                    <tr>
                                        <th class="dt-col-check"></th>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Select multiple --}}
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-slate-800 dark:text-slate-100">Select row (multiple)</h3>
                    <p class="mb-3 text-xs text-slate-500">Checkbox per baris + pilih semua di header (kosong / sebagian / semua).</p>
                    <div class="card card--datatable overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                Terpilih: <span id="ui-dt-multi-count" class="font-medium text-slate-900 dark:text-slate-100">0</span>
                                <span class="text-slate-400">·</span>
                                <span id="ui-dt-multi-label" class="text-slate-500">—</span>
                            </p>
                            <button type="button" id="ui-dt-multi-clear" class="btn-secondary btn-sm" disabled>Kosongkan</button>
                        </div>
                        <div class="card--datatable__body p-4">
                            <table id="ui-dt-multi" class="datatable-main w-full display ui-dt--selectable" style="--table-min-width: 36rem">
                                <thead>
                                    <tr>
                                        <th class="dt-col-check"></th>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Select + per-row inputs --}}
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-slate-800 dark:text-slate-100">Select + input per baris</h3>
                    <p class="mb-3 text-xs text-slate-500">
                        Multi-select dengan field di dalam baris. Klik/tap pada
                        <span class="ui-kit-code">input</span> /
                        <span class="ui-kit-code">select</span> /
                        <span class="ui-kit-code">textarea</span>
                        tidak mengubah pilihan — hanya area baris atau checkbox seleksi.
                    </p>
                    <div class="card card--datatable overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                Terpilih: <span id="ui-dt-input-count" class="font-medium text-slate-900 dark:text-slate-100">0</span>
                                <span class="text-slate-400">·</span>
                                <span id="ui-dt-input-label" class="text-slate-500">—</span>
                            </p>
                            <button type="button" id="ui-dt-input-clear" class="btn-secondary btn-sm" disabled>Kosongkan</button>
                        </div>
                        <div class="card--datatable__body p-4">
                            <table id="ui-dt-input" class="datatable-main w-full display ui-dt--selectable" style="--table-min-width: 44rem">
                                <thead>
                                    <tr>
                                        <th class="dt-col-check"></th>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Poin</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- With filter --}}
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-slate-800 dark:text-slate-100">Dengan filter</h3>
                    <p class="mb-3 text-xs text-slate-500">Pola <span class="ui-kit-code">filter-bar</span> + <span class="ui-kit-code">filter-form</span> (client-side demo; produksi mengirim param ke endpoint DataTables).</p>
                    <div class="card card--datatable overflow-hidden">
                        <div class="filter-bar">
                            <form id="ui-dt-filter-form" class="filter-form" data-filter-mode="client">
                                <div>
                                    <label class="form-label" for="ui-dt-filter-q">Cari</label>
                                    <input type="search" id="ui-dt-filter-q" name="q" class="form-input" placeholder="NIS / nama…">
                                </div>
                                <div>
                                    <label class="form-label" for="ui-dt-filter-kelas">Kelas</label>
                                    <select id="ui-dt-filter-kelas" name="kelas" class="form-input">
                                        <option value="">Semua kelas</option>
                                        <option value="X IPA 1">X IPA 1</option>
                                        <option value="X IPA 2">X IPA 2</option>
                                        <option value="XI IPS 1">XI IPS 1</option>
                                        <option value="XI IPA 2">XI IPA 2</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="ui-dt-filter-status">Status</label>
                                    <select id="ui-dt-filter-status" name="status" class="form-input">
                                        <option value="">Semua status</option>
                                        <option value="aktif">Aktif</option>
                                        <option value="pending">Pending</option>
                                        <option value="nonaktif">Nonaktif</option>
                                    </select>
                                </div>
                                <x-filter-actions />
                            </form>
                        </div>
                        <div class="card--datatable__body p-4 pt-0">
                            <table id="ui-dt-filter" class="datatable-main w-full display" style="--table-min-width: 32rem">
                                <thead>
                                    <tr>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Shared demo payload for JS --}}
            <script type="application/json" id="ui-dt-demo-data">@json($dtDemoRows)</script>
        </x-ui-section>

        {{-- Charts --}}
        <x-ui-section id="charts" title="Chart" description="Chart.js dengan warna brand (CDN).">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="card p-4">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Bar — Pembayaran per Bulan</h3>
                    <canvas id="chart-bar" height="200"></canvas>
                </div>
                <div class="card p-4">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Line — Kehadiran Mingguan</h3>
                    <canvas id="chart-line" height="200"></canvas>
                </div>
                <div class="card p-4 lg:col-span-2 lg:max-w-sm">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Doughnut — Status Tagihan</h3>
                    <canvas id="chart-doughnut" height="180"></canvas>
                </div>
            </div>
        </x-ui-section>

        {{-- Modal --}}
        <x-ui-section id="modal" title="Modal" description="Dialog overlay untuk form dan konfirmasi.">
            <div class="ui-preview">
                <button type="button" class="btn-primary" data-open-modal="ui-demo-modal">Buka Modal Demo</button>
            </div>
        </x-ui-section>

        {{-- Toast --}}
        <x-ui-section id="toast" title="Toast" description="Notifikasi singkat (pojok kanan atas / bawah di mobile). Timer dismiss otomatis berhenti saat hover / sentuh body toast. Pesan kontekstual memakai ActionMessage — detail diurutkan di bawah aksi.">
            <div class="ui-preview space-y-4">
                <div>
                    <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">Tipe dasar</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn-secondary" data-toast-demo="simple-success">Success</button>
                        <button type="button" class="btn-secondary" data-toast-demo="simple-error">Error</button>
                        <button type="button" class="btn-secondary" data-toast-demo="simple-warning">Warning</button>
                        <button type="button" class="btn-secondary" data-toast-demo="simple-info">Info</button>
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">Dengan detail (pola halaman aktif / ActionMessage)</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn-primary" data-toast-demo="detail-success">Siswa diperbarui</button>
                        <button type="button" class="btn-secondary" data-toast-demo="detail-error">Gagal simpan</button>
                        <button type="button" class="btn-secondary" data-toast-demo="detail-warning">RFID diblokir</button>
                        <button type="button" class="btn-secondary" data-toast-demo="detail-rows">Detail berlabel</button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Format: <span class="ui-kit-code">Aksi + verb highlight: detail · detail · detail.</span> — otomatis dipecah menjadi daftar. Hover / sentuh toast untuk menahan dismiss.</p>
                </div>
            </div>
        </x-ui-section>
    </div>
</div>
@endsection

@push('modals')
<x-modal id="ui-demo-modal" title="Modal Demo">
  <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">Ini contoh modal menggunakan komponen <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">x-modal</code>.</p>
  <div class="flex justify-end gap-2">
    <button type="button" data-modal-close="ui-demo-modal" class="btn-secondary">Tutup</button>
    <button type="button" class="btn-primary" data-modal-close="ui-demo-modal">Simpan</button>
  </div>
</x-modal>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/style.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/id.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/monthSelect/index.js"></script>
<script src="{{ asset('js/datepicker.js') }}?v=8"></script>
<script src="{{ asset('js/ui-components.js') }}?v=8"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    initUiCharts?.();
    initUiKitNav?.();
    initUiDateControls?.(document);
    initUiToastDemos?.();
    initUiDataTables?.();
});
</script>
@endpush
