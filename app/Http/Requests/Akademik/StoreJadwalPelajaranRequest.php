<?php

namespace App\Http\Requests\Akademik;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreJadwalPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akademik.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'tahun_akademik_id' => ['required', SoftDeleteRules::exists('tahun_akademik')],
            'kelas_id' => ['required', SoftDeleteRules::exists('kelas')],
            'name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
