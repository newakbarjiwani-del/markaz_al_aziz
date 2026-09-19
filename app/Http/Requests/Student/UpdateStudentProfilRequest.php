<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('students.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_panggilan' => ['nullable', 'string', 'max:100'],
            'golongan_darah' => ['nullable', 'string', 'max:10', Rule::in(['A', 'B', 'AB', 'O', 'A+', 'B+', 'AB+', 'O+', 'A-', 'B-', 'AB-', 'O-'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'golongan_darah.in' => 'Golongan darah tidak valid.',
        ];
    }
}
