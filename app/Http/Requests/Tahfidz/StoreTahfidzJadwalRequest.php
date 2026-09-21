<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\SoftDeleteRules;
use App\Support\TahfidzHari;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTahfidzJadwalRequest extends FormRequest
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
            'halaqoh_id' => ['required', SoftDeleteRules::exists('tahfidz_halaqoh')],
            'day_of_week' => ['required', 'integer', Rule::in(array_keys(TahfidzHari::labels()))],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i', 'after:time_start'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
