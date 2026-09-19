@extends('layouts.alumni')

@section('title', $title)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tracer Study Alumni</h1>
    <p class="mt-2 text-sm text-slate-500">
        Isi formulir ini untuk membantu sekolah memantau perkembangan lulusan.
    </p>

    @if(session('success'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('alumni.tracer.store') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="name" class="form-label">Nama lengkap</label>
            <input id="name" name="name" value="{{ old('name') }}" class="form-input" required maxlength="255">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="nis" class="form-label">NIS (opsional)</label>
                <input id="nis" name="nis" value="{{ old('nis') }}" class="form-input" maxlength="50">
            </div>
            <div>
                <label for="angkatan" class="form-label">Angkatan / tahun lulus</label>
                <input id="angkatan" name="angkatan" value="{{ old('angkatan') }}" class="form-input" maxlength="20">
            </div>
        </div>

        <div>
            <label for="sekolah_id" class="form-label">Sekolah</label>
            <select id="sekolah_id" name="sekolah_id" class="form-input">
                <option value="">Pilih (opsional)</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}" @selected(old('sekolah_id') == $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="phone" class="form-label">Telepon</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" class="form-input" maxlength="30">
            </div>
            <div>
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input" maxlength="255">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="tahun_tracer" class="form-label">Tahun tracer</label>
                <input id="tahun_tracer" name="tahun_tracer" value="{{ old('tahun_tracer', $defaultYear) }}" class="form-input" required>
                @error('tahun_tracer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status_lulusan" class="form-label">Status saat ini</label>
                <select id="status_lulusan" name="status_lulusan" class="form-input" required>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('status_lulusan') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status_lulusan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="institusi" class="form-label">Institusi / perusahaan</label>
                <input id="institusi" name="institusi" value="{{ old('institusi') }}" class="form-input">
            </div>
            <div>
                <label for="jabatan" class="form-label">Jabatan / program studi</label>
                <input id="jabatan" name="jabatan" value="{{ old('jabatan') }}" class="form-input">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="bidang" class="form-label">Bidang</label>
                <input id="bidang" name="bidang" value="{{ old('bidang') }}" class="form-input">
            </div>
            <div>
                <label for="kota" class="form-label">Kota</label>
                <input id="kota" name="kota" value="{{ old('kota') }}" class="form-input">
            </div>
        </div>

        <div>
            <label for="catatan" class="form-label">Catatan / saran</label>
            <textarea id="catatan" name="catatan" class="form-input" rows="4">{{ old('catatan') }}</textarea>
        </div>

        <button type="submit" class="btn-primary">Kirim Respons</button>
    </form>
</div>
@endsection
