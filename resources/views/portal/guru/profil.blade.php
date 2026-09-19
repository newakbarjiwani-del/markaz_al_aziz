@extends('layouts.app')

@section('title', $title)

@section('content')
@php $extra = $profil?->extra_fields ?? []; @endphp

<div class="card p-6">
    <div class="mb-6 border-b pb-4">
        <p class="text-xl font-semibold">{{ $guru->name }}</p>
        <p class="text-muted">NIP {{ $guru->nip }} · {{ $guru->jabatan ?? '-' }}</p>
    </div>

    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <dt class="text-muted text-sm">Jenis Guru</dt>
            <dd class="font-medium">{{ $guru->jenis_guru ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-muted text-sm">Golongan</dt>
            <dd class="font-medium">{{ $guru->golongan ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-muted text-sm">Telepon</dt>
            <dd class="font-medium">{{ $guru->phone ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-muted text-sm">Status</dt>
            <dd class="font-medium">{{ ucfirst($guru->status) }}</dd>
        </div>
        <div>
            <dt class="text-muted text-sm">Pendidikan</dt>
            <dd class="font-medium">{{ $extra['pendidikan'] ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-muted text-sm">Alamat</dt>
            <dd class="font-medium">{{ $extra['alamat'] ?? '-' }}</dd>
        </div>
    </dl>

    @if($guru->riwayatMengajar->isNotEmpty())
        <div class="mt-8">
            <h2 class="mb-3 text-lg font-semibold">Riwayat Mengajar</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted">
                            <th class="pb-2 pr-4">Mata Pelajaran</th>
                            <th class="pb-2 pr-4">Kelas</th>
                            <th class="pb-2">Tahun</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($guru->riwayatMengajar as $row)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-2 pr-4">{{ $row->subject }}</td>
                                <td class="py-2 pr-4">{{ $row->class_name }}</td>
                                <td class="py-2">{{ $row->year }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
