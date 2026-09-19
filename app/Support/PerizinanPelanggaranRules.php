<?php

namespace App\Support;

final class PerizinanPelanggaranRules
{
    /** @return array<string, mixed> */
    public static function rules(bool $required = true): array
    {
        $req = $required ? 'required' : 'nullable';

        return [
            'pelanggaran_jenis_pelanggaran_id' => [$req, 'integer', SoftDeleteRules::exists('jenis_pelanggaran')],
            'pelanggaran_judul' => [$req, 'string', 'max:255'],
            'pelanggaran_keterangan' => ['nullable', 'string', 'max:1000'],
            'pelanggaran_point' => [$req, 'integer', 'min:0', 'max:1000'],
            'pelanggaran_tanggal' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'pelanggaran_jenis_pelanggaran_id.required' => 'Jenis pelanggaran wajib dipilih.',
            'pelanggaran_jenis_pelanggaran_id.exists' => 'Jenis pelanggaran tidak valid.',
            'pelanggaran_judul.required' => 'Judul pelanggaran wajib diisi.',
            'pelanggaran_point.required' => 'Point pelanggaran wajib diisi.',
        ];
    }
}
