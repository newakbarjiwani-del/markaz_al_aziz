<?php

namespace App\Http\Requests\MasterData;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreKamarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => ['nullable', 'string', 'max:50'],
            'nama' => [
                'required',
                'string',
                'max:100',
                SoftDeleteRules::unique('kamar', 'nama'),
            ],
            'blok' => ['nullable', 'string', 'max:100'],
            'kapasitas' => ['nullable', 'integer', 'min:1', 'max:999'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
