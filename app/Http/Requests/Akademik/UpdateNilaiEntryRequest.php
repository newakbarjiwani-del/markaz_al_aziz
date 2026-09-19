<?php

namespace App\Http\Requests\Akademik;

use App\Models\NilaiEntry;
use App\Support\AkademikSemester;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNilaiEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akademik.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'mata_pelajaran_id' => ['required', SoftDeleteRules::exists('mata_pelajaran')],
            'tahun_akademik_id' => ['required', SoftDeleteRules::exists('tahun_akademik')],
            'semester' => ['required', 'string', Rule::in(AkademikSemester::values())],
            'jenis' => ['required', 'string', Rule::in(array_keys(NilaiEntry::jenisLabels()))],
            'kompetensi_dasar_id' => ['nullable', SoftDeleteRules::exists('kompetensi_dasar')],
            'skor' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
