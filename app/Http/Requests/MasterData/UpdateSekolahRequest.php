<?php

namespace App\Http\Requests\MasterData;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSekolahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.update') ?? false;
    }

    public function rules(): array
    {
        $sekolahId = RouteModelId::require($this, 'sekolah', 'Data sekolah tidak valid untuk pembaruan.');

        return [
            'code' => ['required', 'string', 'max:20', SoftDeleteRules::unique('sekolah', 'code', $sekolahId)],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
