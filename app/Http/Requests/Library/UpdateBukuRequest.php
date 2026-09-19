<?php

namespace App\Http\Requests\Library;

use App\Models\Buku;
use App\Support\BukuFormRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBukuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('library.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'isbn' => filled($this->input('isbn')) ? trim((string) $this->input('isbn')) : null,
            'kode_buku' => filled($this->input('kode_buku')) ? trim((string) $this->input('kode_buku')) : null,
            'sekolah_id' => $this->filled('sekolah_id') ? $this->input('sekolah_id') : null,
            'cetak_ke' => $this->filled('cetak_ke') ? $this->input('cetak_ke') : 1,
            'keadaan_baik' => $this->input('keadaan_baik', $this->input('jumlah', 0)),
            'keadaan_rusak_ringan' => $this->input('keadaan_rusak_ringan', 0),
            'keadaan_rusak_berat' => $this->input('keadaan_rusak_berat', 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Buku|null $buku */
        $buku = $this->route('buku');

        return BukuFormRules::rules($buku instanceof Buku ? $buku : null);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return BukuFormRules::messages();
    }

    public function withValidator(Validator $validator): void
    {
        BukuFormRules::validateKeadaanSum($validator);
    }
}
