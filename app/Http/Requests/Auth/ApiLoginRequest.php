<?php

namespace App\Http\Requests\Auth;

use App\Http\Traits\ValidatesTurnstile;
use Illuminate\Foundation\Http\FormRequest;

class ApiLoginRequest extends FormRequest
{
    use ValidatesTurnstile;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'login' => ['required_without:email', 'nullable', 'string', 'max:255'],
            'email' => ['required_without:login', 'nullable', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ], $this->turnstileRules());
    }

    public function messages(): array
    {
        return array_merge([
            'login.required_without' => 'Username atau email wajib diisi.',
            'email.required_without' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ], $this->turnstileMessages());
    }

    public function loginIdentifier(): string
    {
        return trim((string) ($this->input('login') ?? $this->input('email')));
    }

    public function deviceName(): string
    {
        return $this->input('device_name', 'mobile-app');
    }
}
