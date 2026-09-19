<?php

namespace App\Http\Requests\Spmb;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpmbPengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('spmb.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
