<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKasManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'kategori' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'arah' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
            'nominal' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'nominal.required' => 'Nominal wajib diisi.',
            'nominal.min' => 'Nominal minimal Rp 1.',
            'arah.required' => 'Pilih jenis transaksi (pemasukan atau pengeluaran).',
        ];
    }
}
