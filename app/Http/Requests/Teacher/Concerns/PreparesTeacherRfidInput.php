<?php

namespace App\Http\Requests\Teacher\Concerns;

trait PreparesTeacherRfidInput
{
    protected function prepareTeacherRfidInput(): void
    {
        if (! $this->has('rfid_uid')) {
            return;
        }

        $value = trim((string) $this->input('rfid_uid'));

        $this->merge([
            'rfid_uid' => $value !== '' ? $value : null,
        ]);
    }
}
