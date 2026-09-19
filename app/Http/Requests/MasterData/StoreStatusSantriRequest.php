<?php

namespace App\Http\Requests\MasterData;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreStatusSantriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                SoftDeleteRules::unique('status_santri', 'nama'),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
