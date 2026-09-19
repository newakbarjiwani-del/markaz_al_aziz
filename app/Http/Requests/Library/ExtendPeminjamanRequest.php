<?php

namespace App\Http\Requests\Library;

use Illuminate\Foundation\Http\FormRequest;

class ExtendPeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_date.required' => 'Isi jatuh tempo baru.',
            'due_date.date' => 'Format tanggal tidak valid.',
        ];
    }
}
