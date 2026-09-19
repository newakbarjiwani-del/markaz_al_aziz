<?php

namespace App\Http\Requests\MasterData;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use App\Support\TahunAkademikName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTahunAkademikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.update') ?? false;
    }

    public function rules(): array
    {
        $tahunId = RouteModelId::require($this, 'tahunAkademik', 'Data tahun akademik tidak valid untuk pembaruan.');

        return [
            'name' => [
                ...TahunAkademikName::rules(),
                SoftDeleteRules::unique('tahun_akademik', 'name', $tahunId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
