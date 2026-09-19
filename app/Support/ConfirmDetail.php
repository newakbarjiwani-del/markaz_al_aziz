<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Encode confirm-dialog detail for HTML data-confirm-detail attributes.
 *
 * Do not use Illuminate\Support\Js::from() here — that emits JSON.parse('…')
 * for JS source contexts and shows as raw text in dialogs.
 */
final class ConfirmDetail
{
    /**
     * @param  list<array{label?: string, value?: mixed}>|string  $detail
     */
    public static function attr(array|string $detail): HtmlString
    {
        if (is_string($detail)) {
            return new HtmlString(e($detail));
        }

        return new HtmlString(e(json_encode(
            array_values($detail),
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        )));
    }
}
