@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Hafalan Al-Qur’an untuk {{ $siswa->name }}</p>
</div>

@if(session('success'))
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
        {{ session('success') }}
    </div>
@endif

<div class="mb-6 grid gap-4 lg:grid-cols-2">
    <section class="card p-5">
        <h2 class="mb-3 text-lg font-semibold">Mushaf</h2>
        @if($surahs->isEmpty())
            <p class="text-sm text-slate-500">Teks mushaf belum di-seed. Jalankan <code>php artisan db:seed --class=TahfidzQuranSeeder</code>.</p>
        @else
            <div class="mb-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Juz</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($juzList as $juz)
                        <a href="{{ route('portal.siswa.tahfidz.juz', $juz) }}" class="btn-secondary text-sm">Juz {{ $juz }}</a>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Surah</p>
                <div class="max-h-64 space-y-1 overflow-y-auto">
                    @foreach($surahs as $surah)
                        <a href="{{ route('portal.siswa.tahfidz.surah', $surah) }}"
                           class="flex items-center justify-between rounded-md px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                            <span>{{ $surah->label() }}</span>
                            <span class="text-xs text-slate-400" dir="rtl">{{ $surah->name_ar }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section class="card p-5">
        <h2 class="mb-3 text-lg font-semibold">Catat progress</h2>
        <form method="POST" action="{{ route('portal.siswa.tahfidz.progress.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label" for="siswa-surah_id">Surah</label>
                <select name="surah_id" id="siswa-surah_id" class="form-input" required>
                    <option value="">Pilih</option>
                    @foreach($surahs as $surah)
                        <option value="{{ $surah->id }}">{{ $surah->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="siswa-ayah_from">Ayat dari</label>
                    <input type="number" name="ayah_from" id="siswa-ayah_from" class="form-input" min="1" required>
                </div>
                <div>
                    <label class="form-label" for="siswa-ayah_to">Ayat sampai</label>
                    <input type="number" name="ayah_to" id="siswa-ayah_to" class="form-input" min="1" required>
                </div>
            </div>
            <div>
                <label class="form-label" for="siswa-status">Status</label>
                <select name="status" id="siswa-status" class="form-input" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="siswa-note">Catatan</label>
                <textarea name="note" id="siswa-note" class="form-input" rows="2"></textarea>
            </div>
            <button type="submit" class="btn-primary">Simpan</button>
        </form>
    </section>
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <section class="card p-5">
        <h2 class="mb-3 text-lg font-semibold">Target</h2>
        @forelse($targets as $target)
            <div class="border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-800">
                <p class="font-medium">{{ $target->rangeLabel() }}</p>
                <p class="text-slate-500">{{ $target->period === 'daily' ? 'Harian' : 'Mingguan' }}
                    @if($target->due_date) · jatuh tempo {{ $target->due_date->format('d/m/Y') }} @endif
                </p>
            </div>
        @empty
            <p class="text-sm text-slate-500">Belum ada target.</p>
        @endforelse
    </section>
    <section class="card p-5">
        <h2 class="mb-3 text-lg font-semibold">Progress terbaru</h2>
        @forelse($progress as $row)
            <div class="border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-800">
                <p class="font-medium">{{ $row->rangeLabel() }}</p>
                <p class="text-slate-500">{{ $row->statusLabel() }}
                    @if($row->last_reviewed_at) · {{ $row->last_reviewed_at->format('d/m/Y H:i') }} @endif
                </p>
            </div>
        @empty
            <p class="text-sm text-slate-500">Belum ada progress.</p>
        @endforelse
    </section>
</div>

<section class="mt-4 card p-5">
    <h2 class="mb-3 text-lg font-semibold">Rekap halaqoh</h2>
    @forelse($rekaps as $row)
        <div class="border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-800">
            <p class="font-medium">{{ $row->rekap?->program?->label() }} · {{ $row->rekap?->periodLabel() }}</p>
            <p class="text-slate-500">{{ $row->halaqoh?->displayName() }} · Tatsbit {{ $row->tatsbitLabel() }} · Total {{ $row->total_juz }} juz</p>
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada rekap mingguan.</p>
    @endforelse
</section>
@endsection
