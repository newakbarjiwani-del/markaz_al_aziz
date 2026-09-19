<?php

namespace App\Http\Requests\Student\Concerns;

use App\Support\RfidUid;

trait PreparesStudentRfidInput
{
    /**
     * Normalize scanner paste (trim + strip CR/LF). Does not auto-generate RFID.
     */
    protected function prepareStudentRfidInput(): void
    {
        if (! $this->exists('rfid_uid')) {
            return;
        }

        $this->merge([
            'rfid_uid' => RfidUid::sanitize($this->input('rfid_uid')),
        ]);
    }
}