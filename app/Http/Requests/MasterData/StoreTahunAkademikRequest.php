<?php

namespace App\Http\Requests\MasterData;

use App\Support\SoftDeleteRules;
use App\Support\TahunAkademikName;
use Illuminate\Foundation\Http\FormRequest;

class StoreTahunAkademikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                ...TahunAkademikName::rules(),
                SoftDeleteRules::unique('tahun_akademik', 'name'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
