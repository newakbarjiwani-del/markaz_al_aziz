<?php

namespace App\Http\Requests\Library;

use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnPeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'peminjaman_id' => ['required', SoftDeleteRules::exists('peminjaman')],
            'return_date' => ['nullable', 'date'],
            'kondisi_kembali' => ['required', Rule::in(array_keys(PeminjamanBuku::kondisiOptions()))],
            'catatan_kembali' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'peminjaman_id.required' => 'Pilih peminjaman aktif.',
            'kondisi_kembali.required' => 'Pilih kondisi buku saat dikembalikan.',
        ];
    }
}
