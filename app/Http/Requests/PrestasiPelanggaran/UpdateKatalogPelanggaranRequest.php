<?php

namespace App\Http\Requests\PrestasiPelanggaran;

use App\Support\PelanggaranLevel;
use App\Support\PelanggaranSanction;
use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKatalogPelanggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('katalog-pelanggaran.update') ?? false;
    }

    /**
     * Partial edit when the catalog row is already used on sub-data rows:
     * everything is editable EXCEPT `nama` (unique key referenced by records).
     *
     * @return array<string, mixed>
     */
    public static function limitedRules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'kode' => ['nullable', 'string', 'max:50'],
            'level' => PelanggaranLevel::rules(),
            'bidang' => ['required', 'string', 'max:100'],
            'point' => ['nullable', 'integer', 'min:0'],
            'sanction' => PelanggaranSanction::rules(),
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function rules(): array
    {
        $id = RouteModelId::require($this, 'jenisPelanggaran', 'Data katalog pelanggaran tidak valid untuk pembaruan.');

        return [
            ...static::limitedRules(),
            'nama' => ['required', 'string', 'max:255', SoftDeleteRules::unique('jenis_pelanggaran', 'nama', $id)],
        ];
    }

    public function messages(): array
    {
        return [
            'level.required' => 'Level wajib dipilih.',
            'level.in' => 'Level tidak valid.',
            'bidang.required' => 'Bidang wajib diisi.',
            'bidang.max' => 'Bidang maksimal 100 karakter.',
            'nama.required' => 'Nama pelanggaran wajib diisi.',
            'nama.unique' => 'Nama pelanggaran sudah terdaftar di katalog.',
            'point.integer' => 'Point harus berupa angka.',
            'point.min' => 'Point minimal 0.',
            'sanction.in' => 'Sanksi tidak valid.',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
        ];
    }
}
