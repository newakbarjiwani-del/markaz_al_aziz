<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\Student\Concerns\PreparesStudentNisInput;
use App\Http\Requests\Student\Concerns\PreparesStudentRfidInput;
use App\Support\ProfilePhotoRules;
use App\Support\RfidUid;
use App\Support\SiswaStatus;
use App\Support\SoftDeleteRules;
use App\Support\VirtualAccountNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    use PreparesStudentNisInput;
    use PreparesStudentRfidInput;

    public function authorize(): bool
    {
        return $this->user()?->can('students.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'nis' => VirtualAccountNumber::nisRules(),
            'name' => ['required', 'string', 'max:255'],
            'kelas_id' => ['nullable', SoftDeleteRules::exists('kelas')],
            'kamar_id' => ['nullable', SoftDeleteRules::exists('kamar')],
            'status_santri_id' => ['nullable', SoftDeleteRules::exists('status_santri')],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'status' => SiswaStatus::rules(required: true),
            'rfid_uid' => RfidUid::rules(),
            'rfid_blocked' => ['sometimes', 'boolean'],
            'daily_transaction_limit' => ['nullable', 'numeric', 'min:0'],
            ...ProfilePhotoRules::forField('photo'),
        ];
    }

    public function messages(): array
    {
        return array_merge(
            VirtualAccountNumber::nisMessages(),
            RfidUid::messages(),
            ProfilePhotoRules::messages('photo')
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nis')) {
            $this->merge([
                'nis' => VirtualAccountNumber::normalizeNis($this->input('nis')),
            ]);
        }

        if ($this->has('status')) {
            $normalized = SiswaStatus::normalize($this->input('status'));
            if ($normalized !== null) {
                $this->merge(['status' => $normalized]);
            }
        }

        $this->prepareStudentRfidInput();
    }
}
