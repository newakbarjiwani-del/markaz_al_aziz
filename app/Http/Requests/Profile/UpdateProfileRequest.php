<?php

namespace App\Http\Requests\Profile;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', SoftDeleteRules::unique('users', 'email', $userId)],
            'phone' => ['nullable', 'string', 'max:20', SoftDeleteRules::unique('users', 'phone', $userId)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && $this->input('email') === '') {
            $this->merge(['email' => null]);
        }

        if ($this->has('phone') && $this->input('phone') === '') {
            $this->merge(['phone' => null]);
        }
    }
}
