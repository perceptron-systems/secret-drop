<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Base64UrlBytes implements ValidationRule
{
    public function __construct(
        private ?int $exactBytes = null,
        private ?int $minBytes = null,
    ) {
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('messages.val_invalid_base64url'));

            return;
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            $fail(__('messages.val_invalid_base64url'));

            return;
        }

        $base64 = strtr($value, '-_', '+/');
        $padding = (4 - (strlen($base64) % 4)) % 4;
        $decoded = base64_decode($base64.str_repeat('=', $padding), true);

        if ($decoded === false) {
            $fail(__('messages.val_invalid_base64url'));

            return;
        }

        $byteLength = strlen($decoded);

        if ($this->exactBytes !== null && $byteLength !== $this->exactBytes) {
            $fail(__('messages.val_invalid_byte_length', [
                'expected' => $this->exactBytes,
                'actual' => $byteLength,
            ]));

            return;
        }

        if ($this->minBytes !== null && $byteLength < $this->minBytes) {
            $fail(__('messages.val_min_byte_length', [
                'min' => $this->minBytes,
                'actual' => $byteLength,
            ]));
        }
    }
}
