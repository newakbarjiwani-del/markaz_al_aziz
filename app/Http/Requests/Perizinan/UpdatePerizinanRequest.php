<?php

namespace App\Http\Requests\Perizinan;

use App\Models\Perizinan;
use App\Services\Perizinan\PerizinanCheckinService;
use App\Support\PerizinanPelanggaranRules;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePerizinanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('perizinan.update') || $this->user()?->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $rules = [
            'sekolah_id' => ['nullable', 'exists:sekolah,id'],
            'siswa_id' => ['required', 'exists:siswa,id'],
            'jenis_perizinan' => ['required', 'string', 'in:' . implode(',', [
                Perizinan::JENIS_KELUAR_MASUK,
                Perizinan::JENIS_KELUAR_MASUK_PONDOK,
                Perizinan::JENIS_PULANG_LIBUR,
            ])],
            'alasan' => ['required', 'string', 'max:1000'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_sampai' => ['required', 'date', 'after_or_equal:tgl_mulai'],
            'tgl_kembali_aktual' => ['nullable', 'date'],
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'pemberi_izin' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:' . implode(',', [
                Perizinan::STATUS_PENDING,
                Perizinan::STATUS_DISETUJUI,
                Perizinan::STATUS_DITOLAK,
                Perizinan::STATUS_KEMBALI,
                Perizinan::STATUS_TERLAMBAT,
            ])],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:1024'],
        ];

        $perizinan = $this->resolvePerizinan();
        if ($perizinan && $this->requiresLatePelanggaranInput($perizinan)) {
            $rules = array_merge($rules, PerizinanPelanggaranRules::rules(true));
        }

        return $rules;
    }

    public function messages(): array
    {
        return PerizinanPelanggaranRules::messages();
    }

    private function resolvePerizinan(): ?Perizinan
    {
        $model = $this->route('keluar_masuk')
            ?? $this->route('keluar_masuk_pondok')
            ?? $this->route('pulang_libur');

        return $model instanceof Perizinan ? $model : null;
    }

    private function requiresLatePelanggaranInput(Perizinan $perizinan): bool
    {
        if ($perizinan->tgl_kembali_aktual) {
            return false;
        }

        if (! in_array($this->input('status'), [Perizinan::STATUS_KEMBALI, Perizinan::STATUS_TERLAMBAT], true)) {
            return false;
        }

        $returnAt = $this->filled('tgl_kembali_aktual')
            ? Carbon::parse($this->input('tgl_kembali_aktual'))
            : Carbon::now();

        return app(PerizinanCheckinService::class)->isLate($perizinan, $returnAt);
    }
}
