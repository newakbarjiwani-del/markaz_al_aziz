<?php

namespace App\Http\Requests\Ujian;

use App\Support\AkademikSemester;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUjianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('ujian.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'tahun_akademik_id' => ['required', SoftDeleteRules::exists('tahun_akademik')],
            'semester' => ['required', Rule::in(AkademikSemester::values())],
            'mata_pelajaran_id' => ['nullable', SoftDeleteRules::exists('mata_pelajaran')],
            'kelas_id' => ['nullable', SoftDeleteRules::exists('kelas')],
            'guru_id' => ['nullable', SoftDeleteRules::exists('guru')],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
