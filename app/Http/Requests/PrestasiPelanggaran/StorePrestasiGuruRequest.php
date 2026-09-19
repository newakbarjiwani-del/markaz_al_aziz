<?php

namespace App\Http\Requests\PrestasiPelanggaran;

use App\Support\BuktiCatatanRules;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StorePrestasiGuruRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user->can('prestasi-guru.update');
        }

        return $user->can('prestasi-guru.create');
    }

    public function rules(): array
    {
        return [
            'guru_id' => ['required', SoftDeleteRules::exists('guru')],
            'jenis_prestasi_id' => ['nullable', SoftDeleteRules::exists('jenis_prestasi')],
            'judul' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'point' => ['required', 'integer', 'min:0', 'max:1000'],
            ...BuktiCatatanRules::rules(),
        ];
    }

    public function messages(): array
    {
        return array_merge(
            [
                'guru_id.required' => 'Guru wajib dipilih.',
                'guru_id.exists' => 'Guru tidak valid.',
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
