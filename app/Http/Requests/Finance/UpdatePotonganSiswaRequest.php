<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\Finance\Concerns\ValidatesPotonganBillCuts;
use App\Support\PotonganSiswaStatus;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePotonganSiswaRequest extends FormRequest
{
    use ValidatesPotonganBillCuts;

    public function authorize(): bool
    {
        return $this->user()?->can('potongan-tagihan.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'jenis_potongan_id' => ['required', SoftDeleteRules::exists('jenis_potongan')],
            'berlaku_mulai' => ['required', 'date'],
            'berlaku_sampai' => ['required', 'date', 'after_or_equal:berlaku_mulai'],
            'status' => PotonganSiswaStatus::rules(),
            'keterangan' => ['nullable', 'string', 'max:1000'],
            ...$this->billCutRules(),
        ];
    }

    public function withValidator($validator): void
    {
        $this->validateBillCuts($validator);
    }
}
