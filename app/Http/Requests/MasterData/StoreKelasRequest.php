<?php

namespace App\Http\Requests\MasterData;

use App\Models\Kelas;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['required', SoftDeleteRules::exists('sekolah')],
            'kelas' => ['required', 'string', 'max:50'],
            'kelompok' => ['required', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:50'],
            'jenjang' => ['nullable', 'string', 'max:50'],
            'wali_kelas' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->kelasCombinationExists()) {
                $validator->errors()->add('kelompok', 'Kombinasi kelas dan kelompok sudah ada untuk sekolah ini.');
            }
        });
    }

    private function kelasCombinationExists(): bool
    {
        return Kelas::query()
            ->where('sekolah_id', $this->input('sekolah_id'))
            ->where('kelas', $this->input('kelas'))
            ->where('kelompok', $this->input('kelompok'))
            ->exists();
    }
}
