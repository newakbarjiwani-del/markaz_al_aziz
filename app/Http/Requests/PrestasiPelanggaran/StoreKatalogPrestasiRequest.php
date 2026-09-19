<?php

namespace App\Http\Requests\PrestasiPelanggaran;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreKatalogPrestasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('katalog-prestasi.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'kode' => ['nullable', 'string', 'max:50'],
            'bidang' => ['nullable', 'string', 'max:100'],
            'nama' => ['required', 'string', 'max:255', SoftDeleteRules::unique('jenis_prestasi', 'nama')],
            'point' => ['nullable', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama prestasi wajib diisi.',
            'nama.unique' => 'Nama prestasi sudah terdaftar di katalog.',
            'point.integer' => 'Point harus berupa angka.',
            'point.min' => 'Point minimal 0.',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
        ];
    }
}
