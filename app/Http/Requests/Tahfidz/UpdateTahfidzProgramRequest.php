<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTahfidzProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tahfidz.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'tahun_akademik_id' => ['nullable', SoftDeleteRules::exists('tahun_akademik')],
            'name' => ['required', 'string', 'max:100'],
            'angkatan' => ['required', 'integer', 'min:1', 'max:99'],
            'peserta_label' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
