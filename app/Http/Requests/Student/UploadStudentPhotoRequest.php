<?php

namespace App\Http\Requests\Student;

use App\Support\ProfilePhotoRules;
use Illuminate\Foundation\Http\FormRequest;

class UploadStudentPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('students.update') ?? false;
    }

    public function rules(): array
    {
        return ProfilePhotoRules::forField('photo', required: true);
    }

    public function messages(): array
    {
        return ProfilePhotoRules::messages('photo');
    }
}
