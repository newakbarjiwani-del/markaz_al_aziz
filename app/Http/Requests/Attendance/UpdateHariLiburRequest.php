<?php

namespace App\Http\Requests\Attendance;

use App\Support\RouteModelId;
use App\Support\SoftDeleteRules;
use Illuminate\Validation\Validator;

class UpdateHariLiburRequest extends StoreHariLiburRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.update') ?? false;
    }

    public function rules(): array
    {
        $hariLiburId = RouteModelId::require($this, 'hari_libur', 'Data hari libur tidak valid untuk pembaruan.');

        return array_merge($this->sharedFieldRules(), [
            'date' => [
                'required',
                'date',
                SoftDeleteRules::unique('hari_libur', 'date', $hariLiburId)
                    ->where(function ($query) {
                        $sekolahId = $this->input('sekolah_id');
                        if ($sekolahId === null || $sekolahId === '') {
                            return $query->whereNull('sekolah_id');
                        }

                        return $query->where('sekolah_id', $sekolahId);
                    }),
            ],
            'name' => ['required', 'string', 'max:150'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        //
    }

    public function withValidator(Validator $validator): void
    {
        //
    }
}
