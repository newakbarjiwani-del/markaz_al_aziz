<?php

namespace App\Http\Requests\Akademik;

use App\Support\AkademikSemester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKompetensiDasarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akademik.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:50'],
            'deskripsi' => ['required', 'string', 'max:5000'],
            'semester' => ['nullable', 'string', Rule::in(AkademikSemester::values())],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
