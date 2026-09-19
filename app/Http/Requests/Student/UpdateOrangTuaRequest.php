<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\Student\Concerns\ValidatesOrangTuaContact;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrangTuaRequest extends FormRequest
{
    use ValidatesOrangTuaContact;

    public function authorize(): bool
    {
        return $this->user()?->can('students.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->orangTuaFieldRules();
    }
}
