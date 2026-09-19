<?php

namespace App\Http\Requests\Akademik;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJadwalPelajaranSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akademik.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i', 'after:time_start'],
            'mata_pelajaran_id' => ['required', SoftDeleteRules::exists('mata_pelajaran')],
            'guru_id' => ['nullable', SoftDeleteRules::exists('guru')],
            'ruang' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
