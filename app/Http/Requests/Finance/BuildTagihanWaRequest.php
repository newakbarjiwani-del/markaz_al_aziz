<?php

namespace App\Http\Requests\Finance;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class BuildTagihanWaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'tagihan_ids' => ['required', 'array', 'min:1'],
            'tagihan_ids.*' => ['integer', SoftDeleteRules::exists('tagihan')],
            'template_id' => ['nullable', 'integer', SoftDeleteRules::exists('template_pesan_tagihan')],
            'random_template' => ['nullable', 'boolean'],
        ];
    }
}
