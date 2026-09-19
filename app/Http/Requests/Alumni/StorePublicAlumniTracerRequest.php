<?php

namespace App\Http\Requests\Alumni;

use App\Support\AlumniTracerStatus;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicAlumniTracerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50'],
            'angkatan' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tahun_tracer' => ['required', 'string', 'max:10'],
            'status_lulusan' => ['required', Rule::in(AlumniTracerStatus::values())],
            'institusi' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'bidang' => ['nullable', 'string', 'max:255'],
            'kota' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
