<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreTahfidzRekapRequest extends FormRequest
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
            'program_id' => ['required', SoftDeleteRules::exists('tahfidz_program')],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
