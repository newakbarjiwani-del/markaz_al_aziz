<?php

namespace App\Http\Requests\Student\Concerns;

use App\Support\VirtualAccountNumber;

trait PreparesStudentNisInput
{
    protected function prepareForValidation(): void
    {
        if ($this->has('nis')) {
            $this->merge([
                'nis' => VirtualAccountNumber::normalizeNis($this->input('nis')),
            ]);
        }
    }
}
