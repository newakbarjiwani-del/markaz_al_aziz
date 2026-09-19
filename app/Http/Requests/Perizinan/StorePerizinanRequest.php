<?php

namespace App\Http\Requests\Perizinan;

use App\Models\Perizinan;
use Illuminate\Foundation\Http\FormRequest;

class StorePerizinanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('perizinan.create') || $this->user()?->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
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
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'pemberi_izin' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:' . implode(',', [
                Perizinan::STATUS_PENDING,
                Perizinan::STATUS_DISETUJUI,
                Perizinan::STATUS_DITOLAK,
                Perizinan::STATUS_KEMBALI,
                Perizinan::STATUS_TERLAMBAT,
            ])],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:1024'],
        ];
    }
}
