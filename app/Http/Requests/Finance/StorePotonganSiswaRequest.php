<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\Finance\Concerns\ValidatesPotonganBillCuts;
use App\Support\AdminSchoolScope;
use App\Support\PotonganSiswaStatus;
use App\Support\SoftDeleteRules;
use App\Models\Siswa;
use Illuminate\Foundation\Http\FormRequest;

class StorePotonganSiswaRequest extends FormRequest
{
    use ValidatesPotonganBillCuts;

    public function authorize(): bool
    {
        return $this->user()?->can('potongan-tagihan.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
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

        $validator->after(function ($validator) {
            $siswaId = (int) $this->input('siswa_id');
            $scopedSchool = AdminSchoolScope::operatorSekolahId($this->user());

            if ($scopedSchool !== null && $siswaId > 0) {
                $siswaSchool = Siswa::query()->whereKey($siswaId)->value('sekolah_id');
                if ((int) $siswaSchool !== $scopedSchool) {
                    $validator->errors()->add('siswa_id', 'Siswa tidak termasuk sekolah operator.');
                }
            }
        });
    }
}
