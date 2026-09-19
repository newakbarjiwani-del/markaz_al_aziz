<?php

namespace App\Http\Requests\Spmb;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpmbGaleriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('spmb.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'image' => ['required', 'image', 'max:5120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
