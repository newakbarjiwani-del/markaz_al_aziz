<?php

namespace App\Http\Requests\Attendance;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJadwalAbsensiGuruRequest extends FormRequest
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
        $jadwalId = RouteModelId::require($this, 'jadwal_absensi_guru', 'Data jadwal tidak valid untuk pembaruan.');
        $jadwal = $this->route('jadwal_absensi_guru');
        $sekolahId = $this->input('sekolah_id', is_object($jadwal) ? $jadwal->sekolah_id : null);

        return [
            'sekolah_id' => ['required', SoftDeleteRules::exists('sekolah')],
            'name' => [
                'required',
                'string',
                'max:120',
                SoftDeleteRules::unique('jadwal_absensi_guru', 'name', $jadwalId)
                    ->where(fn ($q) => $q->where('sekolah_id', $sekolahId)),
            ],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i', 'different:jam_masuk'],
            'toleransi_menit' => ['required', 'integer', 'min:0', 'max:180'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama jadwal',
            'jam_masuk' => 'jam masuk',
            'jam_pulang' => 'jam pulang',
            'toleransi_menit' => 'toleransi waktu',
        ];
    }
}
