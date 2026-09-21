<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\TahfidzKehadiranStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTahfidzRekapSiswaRequest extends FormRequest
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
            'tatsbit_juz' => ['nullable'],
            'murojaah_juz' => ['nullable'],
            'kehadiran_harian' => ['nullable', 'array'],
            'kehadiran_harian.*' => ['nullable', 'string', Rule::in(TahfidzKehadiranStatus::values())],
            'hadir_hari' => ['nullable', 'integer', 'min:0', 'max:31'],
            'sakit_hari' => ['nullable', 'integer', 'min:0', 'max:31'],
            'pulang_hari' => ['nullable', 'integer', 'min:0', 'max:31'],
            'total_juz' => ['nullable', 'integer', 'min:0', 'max:30'],
            'prestasi' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
        ];
    }
}
