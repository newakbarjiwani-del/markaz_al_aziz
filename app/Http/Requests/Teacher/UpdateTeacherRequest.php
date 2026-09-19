<?php

namespace App\Http\Requests\Teacher;

use App\Http\Requests\Teacher\Concerns\PreparesTeacherRfidInput;
use App\Support\ProfilePhotoRules;
use App\Support\RfidUid;
use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    use PreparesTeacherRfidInput;

    public function authorize(): bool
    {
        return $this->user()?->can('teachers.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareTeacherRfidInput();
    }

    public function rules(): array
    {
        $guruId = RouteModelId::require($this, 'guru', 'Data guru tidak valid untuk pembaruan.');

        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'nip' => ['required', 'string', 'max:50', SoftDeleteRules::unique('guru', 'nip', $guruId)],
            'name' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'jenis_guru' => ['nullable', 'string', 'max:50'],
            'golongan' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'rfid_uid' => RfidUid::guruRules($guruId),
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            ...ProfilePhotoRules::forField('photo'),
        ];
    }

    public function messages(): array
    {
        return array_merge(
            ProfilePhotoRules::messages('photo'),
            RfidUid::guruMessages(),
        );
    }
}
