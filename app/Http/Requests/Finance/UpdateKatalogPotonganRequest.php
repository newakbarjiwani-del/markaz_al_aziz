<?php

namespace App\Http\Requests\Finance;

use App\Support\PotonganTipe;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKatalogPotonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('katalog-potongan.update') ?? false;
    }

    public function rules(): array
    {
        $jenisPotongan = $this->route('jenisPotongan');

        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'kode' => ['nullable', 'string', 'max:50'],
            'nama' => ['required', 'string', 'max:255', SoftDeleteRules::unique('jenis_potongan', 'nama', $jenisPotongan?->id)],
            'tipe_default' => PotonganTipe::rules(),
            'nilai_default' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function limitedRules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'kode' => ['nullable', 'string', 'max:50'],
            'tipe_default' => PotonganTipe::rules(),
            'nilai_default' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $digits = preg_replace('/\D+/', '', (string) $this->input('nilai_default', ''));

        $this->merge([
            'nilai_default' => $digits === '' ? $this->input('nilai_default') : $digits,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (PotonganTipe::normalize($this->input('tipe_default')) === PotonganTipe::PERCENT
                && (int) $this->input('nilai_default') > 100) {
                $validator->errors()->add('nilai_default', 'Nilai persen maksimal 100.');
            }
        });
    }
}
