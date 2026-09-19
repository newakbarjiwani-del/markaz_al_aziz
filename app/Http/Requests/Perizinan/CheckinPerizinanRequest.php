<?php

namespace App\Http\Requests\Perizinan;

use App\Models\Perizinan;
use App\Services\Perizinan\PerizinanCheckinService;
use App\Support\PerizinanPelanggaranRules;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CheckinPerizinanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('perizinan.update') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'catatan' => ['nullable', 'string', 'max:2000'],
            'return_at' => ['nullable', 'date'],
        ];

        /** @var Perizinan|null $perizinan */
        $perizinan = $this->route('keluar_masuk')
            ?? $this->route('keluar_masuk_pondok')
            ?? $this->route('pulang_libur');

        if (! $perizinan instanceof Perizinan) {
            return $rules;
        }

        $returnAt = $this->returnAtFor($perizinan);
        $isLate = app(PerizinanCheckinService::class)->isLate($perizinan, $returnAt);

        if ($isLate) {
            $rules = array_merge($rules, PerizinanPelanggaranRules::rules(true));
        }

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(
            [
                'return_at.date' => 'Format waktu kembali tidak valid.',
            ],
            PerizinanPelanggaranRules::messages()
        );
    }

    public function returnAtFor(Perizinan $perizinan): Carbon
    {
        if ($this->filled('return_at')) {
            return Carbon::parse($this->input('return_at'));
        }

        return Carbon::now();
    }

    /** @return array<string, mixed> */
    public function pelanggaranInput(): array
    {
        return $this->only([
            'pelanggaran_jenis_pelanggaran_id',
            'pelanggaran_judul',
            'pelanggaran_keterangan',
            'pelanggaran_point',
            'pelanggaran_tanggal',
        ]);
    }
}
