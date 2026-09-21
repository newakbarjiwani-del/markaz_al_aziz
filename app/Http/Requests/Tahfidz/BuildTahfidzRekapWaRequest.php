<?php

namespace App\Http\Requests\Tahfidz;

use Illuminate\Foundation\Http\FormRequest;

class BuildTahfidzRekapWaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tahfidz.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rekap_siswa_id' => ['required', 'integer', 'exists:tahfidz_rekap_siswa,id'],
        ];
    }
}
