<?php

namespace App\Http\Requests\Akademik;

use App\Support\AkademikSemester;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuildRaporRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akademik.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'tahun_akademik_id' => ['required', SoftDeleteRules::exists('tahun_akademik')],
            'semester' => ['required', 'string', Rule::in(AkademikSemester::values())],
            'catatan_wali' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
