@props([
    'ajaxUrl' => null,
    'classes' => [],
    'id' => 'daily-subject-matrix',
])

@php
    $normalizedClasses = [];
    if (!empty($classes)) {
        foreach ($classes as $k => $v) {
            if ($v instanceof \App\Models\Kelas) {
                $normalizedClasses[$v->id] = $v->name ?? $v->nama_kelas ?? $v->kelas;
            } else {
                $normalizedClasses[$k] = (string) $v;
            }
        }
    }
@endphp

<div id="{{ $id }}" class="daily-subject-matrix-container space-y-4" data-ajax-url="{{ $ajaxUrl }}">
    {{-- Filter Header --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <form class="daily-matrix-filter-form flex flex-wrap items-end gap-3">
            <div>
                <label class="form-label text-xs" for="{{ $id }}-date">Pilih Tanggal</label>
                <input type="date" name="date" id="{{ $id }}-date" value="{{ now()->toDateString() }}" class="form-input text-xs">
            </div>

            <div>
                <label class="form-label text-xs" for="{{ $id }}-kelas">Pilih Kelas</label>
                <select name="kelas_id" id="{{ $id }}-kelas" class="form-select text-xs" required>
                    @if(empty($normalizedClasses))
                        <option value="">Tidak ada kelas tersedia</option>
                    @else
                        @foreach($normalizedClasses as $classId => $className)
                            <option value="{{ $classId }}" @selected($loop->first)>{{ $className }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <button type="submit" class="btn btn-primary text-xs py-2 px-4 inline-flex items-center gap-1.5">
                <x-icon name="filter" size="xs" />
                <span>Tampilkan Matriks Mapel</span>
            </button>
        </form>

        {{-- Legend Bar --}}
        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-3 text-xs dark:border-slate-800">
            <span class="font-semibold text-slate-700 dark:text-slate-300">Keterangan Status per Mapel:</span>
            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 font-medium text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> H = Hadir
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 px-2 py-0.5 font-medium text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                <span class="h-2 w-2 rounded-full bg-rose-500"></span> A = Alpha
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 font-medium text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span> I = Izin
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-sky-100 px-2 py-0.5 font-medium text-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                <span class="h-2 w-2 rounded-full bg-sky-500"></span> S = Sakit
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-orange-100 px-2 py-0.5 font-medium text-orange-800 dark:bg-orange-950/60 dark:text-orange-300">
                <span class="h-2 w-2 rounded-full bg-orange-500"></span> T = Terlambat
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-purple-100 px-2 py-0.5 font-medium text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                <span class="h-2 w-2 rounded-full bg-purple-500"></span> C = Cuti
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <span class="h-2 w-2 rounded-full bg-slate-400"></span> — = Tidak diabsen / pengecualian sesi
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <span class="h-2 w-2 rounded-full bg-slate-400"></span> - = Belum Ada Sesi
            </span>
        </div>
    </div>

    {{-- Matrix Table Container --}}
    <div class="relative min-h-[300px] rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="daily-matrix-loading hidden absolute inset-0 z-20 flex items-center justify-center bg-white/80 backdrop-blur-xs dark:bg-slate-900/80">
            <div class="flex items-center gap-3 font-medium text-slate-700 dark:text-slate-300">
                <svg class="h-5 w-5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Memuat matriks presensi mata pelajaran...</span>
            </div>
        </div>

        <div class="daily-matrix-wrapper overflow-x-auto p-4">
            <div class="daily-matrix-empty hidden py-12 text-center text-slate-500 dark:text-slate-400">
                Silakan pilih kelas dan tanggal untuk menampilkan matriks presensi mata pelajaran.
            </div>

            <table class="daily-matrix-table min-w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-700 dark:bg-slate-800/80 dark:text-slate-200">
                    <tr class="daily-matrix-header-row border-b border-slate-200 dark:border-slate-700">
                        <th class="sticky left-0 z-10 min-w-[40px] bg-slate-50 px-2 py-2.5 text-center font-bold dark:bg-slate-800">#</th>
                        <th class="sticky left-[40px] z-10 min-w-[90px] bg-slate-50 px-2 py-2.5 font-bold dark:bg-slate-800">NIS</th>
                        <th class="sticky left-[130px] z-10 min-w-[160px] bg-slate-50 px-3 py-2.5 font-bold shadow-r dark:bg-slate-800">Nama Siswa</th>
                        <th class="px-2 py-2.5 font-bold min-w-[90px]">Kelas</th>
                        <th class="daily-subjects-container-cell px-0 py-0 border-x border-slate-200 dark:border-slate-700" colspan="1">
                            {{-- Filled dynamically --}}
                        </th>
                        <th colspan="6" class="text-center font-bold px-2 py-2.5 bg-slate-100 dark:bg-slate-800/90">Total Hari Ini</th>
                    </tr>
                </thead>
                <tbody class="daily-matrix-body divide-y divide-slate-100 dark:divide-slate-800">
                    {{-- Filled dynamically via JS --}}
                </tbody>
            </table>
        </div>
    </div>
</div>
