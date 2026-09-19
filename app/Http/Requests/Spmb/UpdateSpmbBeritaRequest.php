<?php

namespace App\Http\Requests\Spmb;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpmbBeritaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('spmb.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
