<?php

namespace App\Http\Requests\Attendance;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class AssignJadwalAbsensiGuruRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guru_ids' => ['nullable', 'array'],
            'guru_ids.*' => [SoftDeleteRules::exists('guru')],
        ];
    }
}
