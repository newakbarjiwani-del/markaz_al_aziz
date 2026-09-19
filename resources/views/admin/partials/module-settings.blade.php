@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card max-w-2xl p-6">
    <form id="settings-form"
          data-fetch-form
          action="{{ route('admin.pengaturan-modul.update', $module) }}"
          method="POST"
          class="space-y-4">
        @csrf
        @method('PUT')
        @foreach($fields as $field)
            <div>
                @if($field['type'] === 'checkbox')
                    <x-form.checkbox :name="$field['key']" :label="$field['label']" :checked="$field['value']" />
                @elseif($field['type'] === 'number')
                    <label for="{{ $field['key'] }}" class="form-label">{{ $field['label'] }}</label>
                    <x-form.amount :name="$field['key']" :id="$field['key']" :value="$field['value']" :min="0" />
                @else
                    <label class="form-label">{{ $field['label'] }}</label>
                    <input type="{{ $field['type'] }}"
                           name="{{ $field['key'] }}"
                           value="{{ $field['value'] }}"
                           class="form-input">
                @endif
            </div>
        @endforeach
        <div class="flex justify-end pt-2">
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</div>
@endsection
