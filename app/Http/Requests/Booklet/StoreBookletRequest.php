<?php

namespace App\Http\Requests\Booklet;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('booklet.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
