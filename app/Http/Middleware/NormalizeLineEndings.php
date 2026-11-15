<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

/**
 * Browsers count a textarea line break as one character for `maxlength` but
 * submit it as "\r\n", so a field filled to its limit would fail the matching
 * server-side `max:` rule. Normalising to "\n" makes both sides agree.
 */
class NormalizeLineEndings extends TransformsRequest
{
    /**
     * @param  string  $key
     * @param  mixed  $value
     */
    protected function transform($key, $value): mixed
    {
        return is_string($value) ? str_replace("\r\n", "\n", $value) : $value;
    }
}
