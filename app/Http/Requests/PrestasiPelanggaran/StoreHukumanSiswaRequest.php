<?php

namespace App\Http\Requests\PrestasiPelanggaran;

use App\Support\BuktiCatatanRules;
use App\Support\HukumanStatus;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreHukumanSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user->can('hukuman-siswa.update');
        }

        return $user->can('hukuman-siswa.create');
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $base = [
            'sanction' => ['required', 'string', 'max:255'],
            'status' => HukumanStatus::rules(),
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            ...BuktiCatatanRules::rules(),
        ];

        if ($isUpdate) {
            return array_merge($base, [
                'bukti_delete' => ['nullable', 'array'],
                'bukti_delete.*' => ['integer', SoftDeleteRules::exists('bukti_catatan')],
            ]);
        }

        return array_merge($base, [
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'pelanggaran_ids' => ['required', 'array', 'min:1'],
            'pelanggaran_ids.*' => ['integer', SoftDeleteRules::exists('pelanggaran_siswa')],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                return;
            }

            $siswaId = $this->integer('siswa_id');
            $ids = array_values(array_unique(array_map('intval', (array) $this->input('pelanggaran_ids', []))));

            if ($siswaId < 1 || $ids === []) {
                return;
            }

            $validCount = \App\Models\PelanggaranSiswa::query()
                ->activePoints()
                ->where('siswa_id', $siswaId)
                ->whereIn('id', $ids)
                ->count();

            if ($validCount !== count($ids)) {
                $validator->errors()->add(
                    'pelanggaran_ids',
                    'Satu atau lebih pelanggaran tidak valid, sudah dihukum, atau bukan milik siswa ini.'
                );
            }
        });
    }

    public function messages(): array
    {
        return array_merge(
            [
                'siswa_id.required' => 'Siswa wajib dipilih.',
                'siswa_id.exists' => 'Siswa tidak valid.',
                'pelanggaran_ids.required' => 'Pilih minimal satu pelanggaran yang akan dihukum.',
                'pelanggaran_ids.min' => 'Pilih minimal satu pelanggaran yang akan dihukum.',
                'sanction.required' => 'Hukuman diterapkan wajib diisi.',
                'sanction.max' => 'Hukuman diterapkan maksimal 255 karakter.',
                'status.required' => 'Status wajib dipilih.',
                'tanggal.required' => 'Tanggal wajib diisi.',
                'tanggal.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            ],
            BuktiCatatanRules::messages()
        );
    }
}
