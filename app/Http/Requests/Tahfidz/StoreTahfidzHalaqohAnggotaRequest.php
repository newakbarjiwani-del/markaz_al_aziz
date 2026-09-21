<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreTahfidzHalaqohAnggotaRequest extends FormRequest
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
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'total_juz' => ['nullable', 'integer', 'min:0', 'max:30'],
        ];
    }
}
