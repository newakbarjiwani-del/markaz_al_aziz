@extends('layouts.spmb', ['spmbNav' => 'daftar'])

@section('title', $title)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Formulir Pendaftaran</h1>

    @if(session('success'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
            {{ session('success') }}
            @if(session('nomor_pendaftaran'))
                <p class="mt-2 font-mono text-base font-semibold">{{ session('nomor_pendaftaran') }}</p>
                <p class="mt-1 text-xs">Simpan nomor ini untuk keperluan verifikasi.</p>
            @endif
        </div>
    @endif

    @if(! $isOpen)
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            Pendaftaran sedang ditutup.
            <a href="{{ route('spmb.pengumuman.index') }}" class="font-medium underline">Lihat pengumuman</a>
            untuk informasi periode berikutnya.
        </div>
    @else
        <p class="mt-2 text-sm text-slate-500">
            Periode aktif: <strong>{{ $periode->name }}</strong>
            @if($periode->closes_at)
                · ditutup {{ $periode->closes_at->translatedFormat('d M Y H:i') }}
            @endif
        </p>

        <form method="POST" action="{{ route('spmb.daftar.store') }}" class="mt-8 space-y-5">
            @csrf
            @error('periode')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div>
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="form-input @error('name') border-red-500 @enderror" maxlength="255">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="gender" class="form-label">Jenis Kelamin</label>
                    <select name="gender" id="gender" class="form-input">
                        <option value="">—</option>
                        <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label for="birth_date" class="form-label">Tanggal Lahir</label>
                    <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date') }}" class="form-input">
                </div>
            </div>

            <div>
                <label for="birth_place" class="form-label">Tempat Lahir</label>
                <input type="text" name="birth_place" id="birth_place" value="{{ old('birth_place') }}" class="form-input" maxlength="255">
            </div>

            <div>
                <label for="address" class="form-label">Alamat</label>
                <textarea name="address" id="address" rows="3" class="form-input">{{ old('address') }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="phone" class="form-label">No. HP Calon</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="form-input" maxlength="30">
                </div>
                <div>
                    <label for="parent_phone" class="form-label">No. HP Orang Tua</label>
                    <input type="text" name="parent_phone" id="parent_phone" value="{{ old('parent_phone') }}" class="form-input" maxlength="30">
                </div>
            </div>

            <div>
                <label for="parent_name" class="form-label">Nama Orang Tua / Wali</label>
                <input type="text" name="parent_name" id="parent_name" value="{{ old('parent_name') }}" class="form-input" maxlength="255">
            </div>

            <button type="submit" class="btn-primary w-full sm:w-auto">Kirim Pendaftaran</button>
        </form>
    @endif
</div>
@endsection
