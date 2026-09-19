<?php

namespace App\Http\Requests\Attendance;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreAbsensiSesiPengecualianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'jadwal_absen_slot_id' => ['required', 'integer', SoftDeleteRules::exists('jadwal_absen_slot')],
            'date' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan wajib diisi.',
            'reason.min' => 'Alasan minimal 3 karakter.',
        ];
    }
}
