@props([
    'id' => 'subject-period-summary',
    'ajaxUrl' => route('admin.absensi.rekap-presensi.subject-period-summary'),
    'classes' => [],
    'pelajarans' => [],
])

<div id="{{ $id }}" class="space-y-4" data-ajax-url="{{ $ajaxUrl }}"
     data-instansi="{{ config('app.nama_instansi', config('app.name')) }}">
    {{-- Filter Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <form class="subject-period-filter-form grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5 items-end">
            <div>
                <label class="form-label text-xs font-semibold text-slate-700 dark:text-slate-300">Pilih Kelas</label>
                <select name="kelas_id" class="subject-period-kelas-select form-select text-xs w-full rounded-xl">
                    @foreach($classes as $cId => $cName)
                        <option value="{{ $cId }}">{{ $cName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label text-xs font-semibold text-slate-700 dark:text-slate-300">Mata Pelajaran</label>
                <select name="pelajaran_id" class="subject-period-pelajaran-select form-select text-xs w-full rounded-xl">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach($pelajarans as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label text-xs font-semibold text-slate-700 dark:text-slate-300">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ now()->startOfMonth()->toDateString() }}" class="subject-period-start-date form-input text-xs w-full rounded-xl">
            </div>

            <div>
                <label class="form-label text-xs font-semibold text-slate-700 dark:text-slate-300">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ now()->toDateString() }}" class="subject-period-end-date form-input text-xs w-full rounded-xl">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition-all">
                    <x-icon name="filter" size="xs" />
                    <span>Tampilkan Rekap</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Class Summary Stat Cards --}}
    <div class="subject-period-stats-container grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Mata Pelajaran & Periode</div>
            <div class="subject-period-stat-mapel text-sm font-bold text-slate-900 dark:text-white truncate mt-1">-</div>
            <div class="subject-period-stat-periode text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">-</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Rata-rata Kehadiran Kelas</div>
            <div class="flex items-center gap-2 mt-1">
                <div class="subject-period-stat-avg text-xl font-bold text-emerald-600 dark:text-emerald-400">0%</div>
                <div class="subject-period-stat-days text-[10px] rounded-md bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">0 Sesi</div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Siswa Aktif</div>
            <div class="subject-period-stat-students text-xl font-bold text-slate-900 dark:text-white mt-1">0 Siswa</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Tergabung dalam kelas</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Perlu Perhatian (&lt;75%)</div>
            <div class="flex items-center gap-2 mt-1">
                <div class="subject-period-stat-risk text-xl font-bold text-rose-600 dark:text-rose-400">0 Siswa</div>
                <div class="text-[10px] rounded-md bg-rose-50 px-2 py-0.5 font-medium text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">Batas Minimum</div>
            </div>
        </div>
    </div>

    {{-- Main Summary Table Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-3.5 dark:border-slate-800">
            <div class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                <x-icon name="table" size="xs" class="text-emerald-600 dark:text-emerald-400" />
                <span>Akumulasi Presensi Siswa</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" class="subject-period-search-input form-input text-xs rounded-xl py-1.5 px-3 w-48" placeholder="Cari nama / NIS...">
                <button type="button"
                        class="subject-period-export-excel btn-secondary"
                        disabled>
                    <x-icon name="file-spreadsheet" size="sm" class="mr-1" />
                    <span class="subject-period-export-label">Export Excel</span>
                </button>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-slate-100 px-5 py-2 text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400">
            <span class="font-semibold text-slate-600 dark:text-slate-300">Keterangan:</span>
            <span><span class="font-bold text-emerald-700 dark:text-emerald-300">H</span> Hadir</span>
            <span><span class="font-bold text-orange-700 dark:text-orange-300">T</span> Terlambat</span>
            <span><span class="font-bold text-amber-700 dark:text-amber-300">I</span> Izin</span>
            <span><span class="font-bold text-sky-700 dark:text-sky-300">S</span> Sakit</span>
            <span><span class="font-bold text-purple-700 dark:text-purple-300">C</span> Cuti</span>
            <span><span class="font-bold text-rose-700 dark:text-rose-300">A</span> Alpha</span>
            <span class="text-slate-400">· % = (H + T) / Total Sesi</span>
        </div>

        {{-- Table Container --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-slate-600 dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-400">
                        <th class="px-3 py-3 text-center font-bold min-w-[36px]">#</th>
                        <th class="px-3 py-3 font-bold min-w-[90px]">NIS</th>
                        <th class="px-3 py-3 font-bold min-w-[160px]">Nama Siswa</th>
                        <th class="px-3 py-3 font-bold min-w-[90px]">Kelas</th>
                        <th class="px-3 py-3 font-bold min-w-[130px]">Mata Pelajaran</th>
                        <th class="px-2 py-3 text-center font-bold min-w-[70px]">Total Sesi</th>
                        <th class="px-2 py-3 text-center font-bold text-emerald-700 bg-emerald-50/60 dark:bg-emerald-950/30 dark:text-emerald-300 min-w-[50px]">H</th>
                        <th class="px-2 py-3 text-center font-bold text-orange-700 bg-orange-50/60 dark:bg-orange-950/30 dark:text-orange-300 min-w-[50px]">T</th>
                        <th class="px-2 py-3 text-center font-bold text-amber-700 bg-amber-50/60 dark:bg-amber-950/30 dark:text-amber-300 min-w-[50px]">I</th>
                        <th class="px-2 py-3 text-center font-bold text-sky-700 bg-sky-50/60 dark:bg-sky-950/30 dark:text-sky-300 min-w-[50px]">S</th>
                        <th class="px-2 py-3 text-center font-bold text-purple-700 bg-purple-50/60 dark:bg-purple-950/30 dark:text-purple-300 min-w-[50px]">C</th>
                        <th class="px-2 py-3 text-center font-bold text-rose-700 bg-rose-50/60 dark:bg-rose-950/30 dark:text-rose-300 min-w-[50px]">A</th>
                        <th class="px-3 py-3 text-center font-bold min-w-[90px]">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="subject-period-table-body divide-y divide-slate-100 dark:divide-slate-800">
                    {{-- Rows rendered by JS --}}
                </tbody>
            </table>
        </div>

        {{-- Loading Overlay --}}
        <div class="subject-period-loading hidden p-8 text-center text-slate-500">
            <div class="inline-flex items-center gap-2 text-xs font-medium">
                <svg class="h-4 w-4 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Memuat data akumulasi presensi...</span>
            </div>
        </div>

        {{-- Empty State --}}
        <div class="subject-period-empty hidden p-8 text-center text-xs text-slate-500">
            Tidak ada data presensi siswa ditemukan.
        </div>
    </div>
</div>
