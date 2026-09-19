<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetGuruAccountPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('teachers.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
