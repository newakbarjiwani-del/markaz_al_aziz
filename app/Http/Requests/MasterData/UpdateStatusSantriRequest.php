<?php

namespace App\Http\Requests\MasterData;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusSantriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.update') ?? false;
    }

    public function rules(): array
    {
        $statusSantriId = RouteModelId::resolve($this, 'statusSantri');

        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                SoftDeleteRules::unique('status_santri', 'nama', $statusSantriId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
