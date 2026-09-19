<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTahfidzTargetRequest extends FormRequest
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
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'range_type' => ['required', Rule::in(['juz', 'ayat'])],
            'juz' => ['nullable', 'integer', 'min:1', 'max:30', 'required_if:range_type,juz'],
            'surah_id' => ['nullable', Rule::exists('tahfidz_surah', 'id'), 'required_if:range_type,ayat'],
            'ayah_from' => ['nullable', 'integer', 'min:1', 'required_if:range_type,ayat'],
            'ayah_to' => ['nullable', 'integer', 'min:1', 'required_if:range_type,ayat', 'gte:ayah_from'],
            'period' => ['required', Rule::in(['daily', 'weekly'])],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
