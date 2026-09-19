@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-6 p-5">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">PIN Cashless Anak</h2>
    <p class="text-muted mt-1 text-sm">
        PIN 4 digit dipakai saat admin/operator menarik saldo cashless yang melebihi limit harian.
        Set PIN per anak. Jika sudah ada PIN, isi PIN saat ini sebelum mengganti.
    </p>
</div>

@if(count($children) === 0)
    <div class="card p-8 text-center text-sm text-slate-500 dark:text-slate-400">
        Belum ada anak terhubung ke akun ini.
    </div>
@else
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($children as $child)
            <div class="card p-5">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $child['name'] }}</p>
                        <p class="text-muted text-sm">NIS {{ $child['nis'] }} · {{ $child['kelas'] }}</p>
                    </div>
                    @if($child['pin_set'])
                        <span class="rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">PIN aktif</span>
                    @else
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300">Belum ada PIN</span>
                    @endif
                </div>

                <form data-fetch-form
                      action="{{ route('portal.ortu.pin-cashless.update', $child['id']) }}"
                      method="POST"
                      class="space-y-3"
                      data-reload-page>
                    @csrf
                    @method('PUT')
                    @if($child['pin_set'])
                        <div>
                            <label class="form-label" for="ortu-current-pin-{{ $child['id'] }}">PIN Saat Ini</label>
                            <input type="password"
                                   name="current_pin"
                                   id="ortu-current-pin-{{ $child['id'] }}"
                                   class="form-input font-mono tracking-widest"
                                   inputmode="numeric"
                                   maxlength="4"
                                   autocomplete="off"
                                   required>
                        </div>
                    @endif
                    <div>
                        <label class="form-label" for="ortu-pin-{{ $child['id'] }}">PIN Baru (4 digit)</label>
                        <input type="password"
                               name="pin"
                               id="ortu-pin-{{ $child['id'] }}"
                               class="form-input font-mono tracking-widest"
                               inputmode="numeric"
                               maxlength="4"
                               autocomplete="new-password"
                               required>
                    </div>
                    <div>
                        <label class="form-label" for="ortu-pin-confirmation-{{ $child['id'] }}">Ulangi PIN Baru</label>
                        <input type="password"
                               name="pin_confirmation"
                               id="ortu-pin-confirmation-{{ $child['id'] }}"
                               class="form-input font-mono tracking-widest"
                               inputmode="numeric"
                               maxlength="4"
                               autocomplete="new-password"
                               required>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="submit" class="btn-primary">
                            <x-icon name="key" size="sm" class="mr-1" />
                            {{ $child['pin_set'] ? 'Ubah PIN' : 'Set PIN' }}
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
@endif
@endsection
