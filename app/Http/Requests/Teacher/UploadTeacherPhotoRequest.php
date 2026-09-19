<?php

namespace App\Http\Requests\Teacher;

use App\Support\ProfilePhotoRules;
use Illuminate\Foundation\Http\FormRequest;

class UploadTeacherPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('teachers.update') ?? false;
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
