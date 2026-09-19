<?php

namespace App\Http\Requests\Import;

use App\Support\AdminSekolahResolver;
use App\Support\ImportSpreadsheetRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportSpreadsheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = ImportSpreadsheetRules::forField();

        if ($this->isBukuImport()) {
            $rules['sekolah_id'] = ['nullable', 'integer', Rule::exists('sekolah', 'id')];

            return $rules;
        }

        if (AdminSekolahResolver::requiresSchoolSelection($this->user())) {
            $rules['sekolah_id'] = ['required', 'integer', Rule::exists('sekolah', 'id')];
        }

        return $rules;
    }

    private function isBukuImport(): bool
    {
        return str_contains($this->path(), 'impor-buku');
    }

    public function messages(): array
    {
        return array_merge(ImportSpreadsheetRules::messages(), [
            'sekolah_id.required' => 'Pilih sekolah tujuan import.',
            'sekolah_id.exists' => 'Sekolah tujuan import tidak ditemukan.',
        ]);
    }
}
