@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
        <p class="mt-1 text-sm text-slate-500">Tanggal {{ $rekap->periodLabel() }} · {{ $rekap->statusLabel() }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.tahfidz.rekap.index') }}" class="btn-secondary">Daftar rekap</a>
        <a href="{{ route('admin.tahfidz.kirim-wa.index', ['rekap_id' => $rekap->id]) }}" class="btn-primary">Kirim WA</a>
        @can('tahfidz.update')
            <form method="POST" action="{{ route('admin.tahfidz.rekap.siap', $rekap) }}">
                @csrf
                <button type="submit" class="btn-secondary">Tandai siap</button>
            </form>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="mb-6 card p-5">
    <h2 class="mb-2 text-lg font-semibold">Preview rekap lengkap</h2>
    <pre class="whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{{ $preview }}</pre>
</div>

@forelse($grouped as $rows)
    @php
        $halaqoh = $rows->first()?->halaqoh;
        $dates = $sessionDates[$halaqoh?->id] ?? [];
    @endphp
    <section class="mb-6 card p-5">
        <h2 class="mb-4 text-lg font-semibold">{{ $halaqoh?->displayName() }}</h2>
        @foreach($rows as $row)
            <form method="POST"
                  action="{{ route('admin.tahfidz.rekap.baris.update', [$rekap, $row]) }}"
                  data-fetch-form
                  data-method="PUT"
                  class="mb-6 border-b border-slate-100 pb-6 last:mb-0 last:border-0 last:pb-0 dark:border-slate-800">
                @csrf
                @method('PUT')
                <p class="mb-3 font-medium">{{ $row->siswa?->name }} <span class="text-xs text-slate-500">{{ $row->siswa?->nis }}</span></p>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="form-label">Tatsbit (juz, pisahkan koma)</label>
                        <input type="text" name="tatsbit_juz" class="form-input" value="{{ implode(',', $row->tatsbit_juz ?? []) }}" placeholder="1,2,4">
                    </div>
                    <div>
                        <label class="form-label">Muroja'ah partner</label>
                        <input type="text" name="murojaah_juz" class="form-input" value="{{ implode(',', $row->murojaah_juz ?? []) }}" placeholder="6,7,8">
                    </div>
                </div>
                @if($dates !== [])
                    <div class="mt-4">
                        <p class="form-label">Absensi sesuai jadwal</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($dates as $date)
                                <label class="text-xs">
                                    {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('D j/n') }}
                                    <select name="kehadiran_harian[{{ $date }}]" class="form-input mt-1">
                                        @foreach($kehadiran as $value => $label)
                                            <option value="{{ $value }}" @selected(($row->kehadiran_harian[$date] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="form-label">Hadir (hari)</label>
                            <input type="number" name="hadir_hari" class="form-input" min="0" value="{{ $row->hadir_hari }}">
                        </div>
                        <div>
                            <label class="form-label">Sakit (hari)</label>
                            <input type="number" name="sakit_hari" class="form-input" min="0" value="{{ $row->sakit_hari }}">
                        </div>
                        <div>
                            <label class="form-label">Pulang (hari)</label>
                            <input type="number" name="pulang_hari" class="form-input" min="0" value="{{ $row->pulang_hari }}">
                        </div>
                    </div>
                @endif
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Total seluruh hafalan (juz)</label>
                        <input type="number" name="total_juz" class="form-input" min="0" max="30" value="{{ $row->total_juz }}">
                    </div>
                    <div>
                        <label class="form-label">Prestasi</label>
                        <input type="text" name="prestasi" class="form-input" value="{{ $row->prestasi }}" placeholder="Tasmi' 5 juz sekali duduk (1-5)">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn-primary text-sm">Simpan {{ $row->siswa?->name }}</button>
                </div>
            </form>
        @endforeach
    </section>
@empty
    <div class="card p-8 text-center text-slate-500">Belum ada anggota di program ini. Tambahkan anggota halaqoh terlebih dahulu.</div>
@endforelse
@endsection
