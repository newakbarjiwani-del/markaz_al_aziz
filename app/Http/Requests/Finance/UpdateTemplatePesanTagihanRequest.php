<?php

namespace App\Http\Requests\Finance;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use App\Support\TagihanPesanKategori;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplatePesanTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.update') ?? false;
    }

    public function rules(): array
    {
        $templateId = RouteModelId::resolve($this, 'templatePesanTagihan');

        return [
            'nama' => [
                'required',
                'string',
                'max:120',
                SoftDeleteRules::unique('template_pesan_tagihan', 'nama', $templateId),
            ],
            'kategori' => TagihanPesanKategori::rules(),
            'isi_pesan' => ['required', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
