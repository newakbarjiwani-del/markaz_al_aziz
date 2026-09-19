<?php

namespace App\Support;

final class EditRecordPayload
{
    /**
     * Encode edit fields for a data-edit-record HTML attribute.
     * Base64 avoids broken JSON when values contain quotes/newlines/long text.
     *
     * @param  array<string, mixed>  $fields
     */
    public static function encode(array $fields): string
    {
        return base64_encode(json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
