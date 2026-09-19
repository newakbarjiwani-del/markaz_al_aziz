<?php

namespace App\Http\Requests\Booklet;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookletPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('booklet.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasBody = filled($this->input('body'));
            $hasFile = $this->hasFile('file');
            $hasTitle = filled($this->input('title'));

            if (! $hasBody && ! $hasFile && ! $hasTitle) {
                $validator->errors()->add('body', 'Isi judul, teks, atau lampiran file.');
            }
        });
    }
}
