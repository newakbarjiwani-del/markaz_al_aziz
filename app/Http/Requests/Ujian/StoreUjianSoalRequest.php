<?php

namespace App\Http\Requests\Ujian;

use App\Models\UjianSoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUjianSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('ujian.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(array_keys(UjianSoal::jenisLabels()))],
            'pertanyaan' => ['required', 'string'],
            'poin' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'opsi' => ['nullable', 'array', 'min:2'],
            'opsi.*.key' => ['required_with:opsi', 'string', 'max:10'],
            'opsi.*.label' => ['required_with:opsi', 'string', 'max:1000'],
            'kunci' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('jenis') !== UjianSoal::JENIS_PILIHAN_GANDA) {
                return;
            }

            $opsi = $this->input('opsi', []);
            if (! is_array($opsi) || count($opsi) < 2) {
                $validator->errors()->add('opsi', 'Pilihan ganda membutuhkan minimal 2 opsi.');
            }

            $kunci = (string) $this->input('kunci', '');
            $keys = collect($opsi)->pluck('key')->filter()->map(fn ($k) => (string) $k)->all();
            if ($kunci === '' || ! in_array($kunci, $keys, true)) {
                $validator->errors()->add('kunci', 'Kunci harus salah satu key opsi.');
            }
        });
    }
}
