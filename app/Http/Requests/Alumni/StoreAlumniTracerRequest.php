<?php

namespace App\Http\Requests\Alumni;

use App\Support\AlumniTracerStatus;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlumniTracerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('alumni.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'tahun_tracer' => ['required', 'string', 'max:10'],
            'status_lulusan' => ['required', Rule::in(AlumniTracerStatus::values())],
            'institusi' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'bidang' => ['nullable', 'string', 'max:255'],
            'kota' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'alumni_id' => ['nullable', SoftDeleteRules::exists('alumni')],
        ];
    }
}
