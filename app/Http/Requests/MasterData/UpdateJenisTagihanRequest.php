<?php

namespace App\Http\Requests\MasterData;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJenisTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function limitedRules(): array
    {
        return [
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_spp' => ['nullable', 'boolean'],
        ];
    }

    public function rules(): array
    {
        $jenisTagihanId = RouteModelId::require($this, 'jenisTagihan', 'Data jenis tagihan tidak valid untuk pembaruan.');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                SoftDeleteRules::unique('jenis_tagihan', 'name', $jenisTagihanId),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_spp' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
