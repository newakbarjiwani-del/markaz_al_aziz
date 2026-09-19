<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\SoftDeleteRules;
use App\Support\TahfidzProgressStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTahfidzProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tahfidz.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'surah_id' => ['required', Rule::exists('tahfidz_surah', 'id')],
            'ayah_from' => ['required', 'integer', 'min:1'],
            'ayah_to' => ['required', 'integer', 'min:1', 'gte:ayah_from'],
            'status' => ['required', Rule::in(TahfidzProgressStatus::values())],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
