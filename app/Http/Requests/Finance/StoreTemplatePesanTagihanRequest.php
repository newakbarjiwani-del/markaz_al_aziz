<?php

namespace App\Http\Requests\Finance;

use App\Support\SoftDeleteRules;
use App\Support\TagihanPesanKategori;
use Illuminate\Foundation\Http\FormRequest;

class StoreTemplatePesanTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                'max:120',
                SoftDeleteRules::unique('template_pesan_tagihan', 'nama'),
            ],
            'kategori' => TagihanPesanKategori::rules(),
            'isi_pesan' => ['required', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
