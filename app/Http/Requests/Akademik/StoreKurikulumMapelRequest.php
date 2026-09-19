<?php

namespace App\Http\Requests\Akademik;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreKurikulumMapelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akademik.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'mata_pelajaran_id' => ['required', SoftDeleteRules::exists('mata_pelajaran')],
            'tingkat' => ['nullable', 'integer', 'min:1', 'max:12'],
            'jam_mingguan' => ['nullable', 'integer', 'min:0', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
