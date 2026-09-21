<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTahfidzHalaqohRequest extends FormRequest
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
            'program_id' => ['required', SoftDeleteRules::exists('tahfidz_program')],
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'guru_id' => ['required', SoftDeleteRules::exists('guru')],
            'name' => ['nullable', 'string', 'max:150'],
        ];
    }
}
