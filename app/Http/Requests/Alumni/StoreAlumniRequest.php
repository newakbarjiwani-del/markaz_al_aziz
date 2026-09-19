<?php

namespace App\Http\Requests\Alumni;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('alumni.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'siswa_id' => ['nullable', SoftDeleteRules::exists('siswa')],
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50'],
            'angkatan' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
