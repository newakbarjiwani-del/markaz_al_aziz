@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
        <p class="mt-1 text-sm text-slate-500">Tanggal {{ $rekap->periodLabel() }}</p>
    </div>
    <a href="{{ route('portal.guru.tahfidz.rekap.index') }}" class="btn-secondary">Kembali</a>
</div>

@if(session('success'))
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

@forelse($rows as $row)
    @php $dates = $sessionDates[$row->halaqoh_id] ?? []; @endphp
    <form method="POST" action="{{ route('portal.guru.tahfidz.rekap.baris.update', [$rekap, $row]) }}" class="mb-6 card p-5">
        @csrf
        @method('PUT')
        <p class="mb-3 font-medium">{{ $row->siswa?->name }}</p>
        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <label class="form-label">Tatsbit</label>
                <input type="text" name="tatsbit_juz" class="form-input" value="{{ implode(',', $row->tatsbit_juz ?? []) }}">
            </div>
            <div>
                <label class="form-label">Muroja'ah partner</label>
                <input type="text" name="murojaah_juz" class="form-input" value="{{ implode(',', $row->murojaah_juz ?? []) }}">
            </div>
        </div>
        @if($dates !== [])
            <div class="mt-4 flex flex-wrap gap-3">
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
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="form-label">Hadir</label>
                    <input type="number" name="hadir_hari" class="form-input" min="0" value="{{ $row->hadir_hari }}">
                </div>
                <div>
                    <label class="form-label">Sakit</label>
                    <input type="number" name="sakit_hari" class="form-input" min="0" value="{{ $row->sakit_hari }}">
                </div>
                <div>
                    <label class="form-label">Pulang</label>
                    <input type="number" name="pulang_hari" class="form-input" min="0" value="{{ $row->pulang_hari }}">
                </div>
            </div>
        @endif
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">Total juz</label>
                <input type="number" name="total_juz" class="form-input" min="0" max="30" value="{{ $row->total_juz }}">
            </div>
            <div>
                <label class="form-label">Prestasi</label>
                <input type="text" name="prestasi" class="form-input" value="{{ $row->prestasi }}">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
@empty
    <div class="card p-8 text-center text-slate-500">Tidak ada anggota pada halaqoh Anda untuk rekap ini.</div>
@endforelse
@endsection
