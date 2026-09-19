<?php

namespace App\Http\Requests\PrestasiPelanggaran;

use App\Support\BuktiCatatanRules;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StorePelanggaranSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user->can('pelanggaran-siswa.update');
        }

        return $user->can('pelanggaran-siswa.create');
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('POST') && $this->filled('siswa_id') && ! $this->filled('siswa_ids')) {
            $this->merge([
                'siswa_ids' => [(int) $this->input('siswa_id')],
            ]);
        }
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $siswaRules = $isUpdate
            ? ['siswa_id' => ['required', SoftDeleteRules::exists('siswa')]]
            : [
                'siswa_ids' => ['required', 'array', 'min:1'],
                'siswa_ids.*' => ['required', 'integer', SoftDeleteRules::exists('siswa')],
            ];

        return array_merge($siswaRules, [
            'jenis_pelanggaran_id' => ['required', 'integer', SoftDeleteRules::exists('jenis_pelanggaran')],
            'judul' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'point' => ['required', 'integer', 'min:0', 'max:1000'],
            ...BuktiCatatanRules::rules(),
        ]);
    }

    public function messages(): array
    {
        return array_merge(
            [
                'siswa_id.required' => 'Siswa wajib dipilih.',
                'siswa_id.exists' => 'Siswa tidak valid.',
                'siswa_ids.required' => 'Minimal satu siswa wajib dipilih.',
                'siswa_ids.min' => 'Minimal satu siswa wajib dipilih.',
                'siswa_ids.*.exists' => 'Salah satu siswa tidak valid.',
                'jenis_pelanggaran_id.required' => 'Jenis pelanggaran wajib dipilih.',
                'jenis_pelanggaran_id.exists' => 'Jenis pelanggaran tidak valid.',
                'judul.required' => 'Judul wajib diisi.',
                'judul.max' => 'Judul maksimal 255 karakter.',
                'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
                'tanggal.required' => 'Tanggal wajib diisi.',
                'tanggal.date' => 'Format tanggal tidak valid.',
                'tanggal.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
                'point.required' => 'Point wajib diisi.',
                'point.integer' => 'Point harus berupa angka.',
                'point.min' => 'Point minimal 0.',
                'point.max' => 'Point maksimal 1000.',
            ],
            BuktiCatatanRules::messages()
        );
    }
}
