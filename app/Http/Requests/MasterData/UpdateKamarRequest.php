<?php

namespace App\Http\Requests\MasterData;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKamarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.update') ?? false;
    }

    public function rules(): array
    {
        $kamarId = RouteModelId::resolve($this, 'kamar');

        return [
            'kode' => ['nullable', 'string', 'max:50'],
            'nama' => [
                'required',
                'string',
                'max:100',
                SoftDeleteRules::unique('kamar', 'nama', $kamarId),
            ],
            'blok' => ['nullable', 'string', 'max:100'],
            'kapasitas' => ['nullable', 'integer', 'min:1', 'max:999'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
