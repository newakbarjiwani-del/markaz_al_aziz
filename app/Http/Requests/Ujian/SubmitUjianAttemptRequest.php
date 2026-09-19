<?php

namespace App\Http\Requests\Ujian;

use Illuminate\Foundation\Http\FormRequest;

class SubmitUjianAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('siswa') ?? false;
    }

    public function rules(): array
    {
        return [
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
