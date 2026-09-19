<?php

namespace App\Http\Requests\Attendance;

use App\Support\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJadwalGuruAttendanceRequest extends FormRequest
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
        return [
            'method' => ['required', Rule::in(['rfid', 'manual'])],
            'guru_id' => ['required_if:method,manual', 'nullable', 'integer', 'exists:guru,id'],
            'rfid_uid' => ['required_if:method,rfid', 'nullable', 'string', 'max:64'],
            'status' => AttendanceStatus::validationRule(),
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
