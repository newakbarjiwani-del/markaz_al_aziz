<?php

namespace App\Http\Requests\PrestasiPelanggaran;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKatalogPrestasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('katalog-prestasi.update') ?? false;
    }

    /**
     * Partial edit when the catalog row is already used:
     * everything is editable EXCEPT `nama`.
     *
     * @return array<string, mixed>
     */
    public static function limitedRules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'kode' => ['nullable', 'string', 'max:50'],
            'bidang' => ['nullable', 'string', 'max:100'],
            'point' => ['nullable', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function rules(): array
    {
        $id = RouteModelId::require($this, 'jenisPrestasi', 'Data katalog prestasi tidak valid untuk pembaruan.');

        return [
            ...static::limitedRules(),
            'nama' => ['required', 'string', 'max:255', SoftDeleteRules::unique('jenis_prestasi', 'nama', $id)],
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
