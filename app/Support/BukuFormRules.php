<?php

namespace App\Support;

use App\Models\Buku;
use Illuminate\Validation\Validator;

final class BukuFormRules
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(?Buku $buku = null): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'kode_buku' => ['nullable', 'string', 'max:50'],
            'isbn' => [
                'nullable',
                'string',
                'max:32',
                SoftDeleteRules::unique('buku', 'isbn_key', $buku),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'pengarang' => ['nullable', 'string', 'max:255'],
            'penerbit' => ['nullable', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'tahun_terbit' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'cetak_ke' => ['nullable', 'integer', 'min:1', 'max:999'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:99999'],
            'keadaan_baik' => ['required', 'integer', 'min:0', 'max:99999'],
            'keadaan_rusak_ringan' => ['required', 'integer', 'min:0', 'max:99999'],
            'keadaan_rusak_berat' => ['required', 'integer', 'min:0', 'max:99999'],
            'tanggal_penerimaan' => ['nullable', 'date'],
            'sumber_dana' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'judul.required' => 'Judul buku wajib diisi.',
            'jumlah.required' => 'Jumlah eksemplar wajib diisi.',
            'jumlah.min' => 'Jumlah eksemplar minimal 1.',
            'isbn.unique' => 'ISBN sudah dipakai buku lain.',
            'keadaan_baik.required' => 'Isi jumlah keadaan baik.',
            'keadaan_rusak_ringan.required' => 'Isi jumlah rusak ringan.',
            'keadaan_rusak_berat.required' => 'Isi jumlah rusak berat.',
        ];
    }

    public static function validateKeadaanSum(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $validator->getData();
            $jumlah = (int) ($data['jumlah'] ?? 0);
            $total = (int) ($data['keadaan_baik'] ?? 0)
                + (int) ($data['keadaan_rusak_ringan'] ?? 0)
                + (int) ($data['keadaan_rusak_berat'] ?? 0);

            if ($total !== $jumlah) {
                $validator->errors()->add(
                    'jumlah',
                    'Jumlah keadaan (baik + rusak ringan + rusak berat) harus sama dengan jumlah eksemplar.'
                );
            }
        });
    }
}
