<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor to sanitize sensitive data from logs.
 *
 * Zero-knowledge principle: tokens, URLs with fragments, ciphertext,
 * and other sensitive data must never appear in logs.
 */
class SanitizeProcessor implements ProcessorInterface
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'secret',
        'token',
        'admin_token',
        'api_key',
        'apikey',
        'authorization',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'private_key',
        'ciphertext',
        'cipher_meta',
        'passphrase',
        'key',
        'fragment',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $this->sanitizeArray($record->context);
        $extra = $this->sanitizeArray($record->extra);
        $message = $this->sanitizeString($record->message);

        return $record->with(
            message: $message,
            context: $context,
            extra: $extra
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    private function sanitizeString(string $value): string
    {
        $patterns = [
            // JSON keys with sensitive values
            '/("(?:password|secret|token|api_key|authorization|ciphertext|passphrase)":\s*")[^"]*(")/i' => '$1[REDACTED]$2',

            // Bearer tokens
            '/(Bearer\s+)[^\s]+/i' => '$1[REDACTED]',

            // URLs with fragments (the fragment contains the encryption key)
            '/(https?:\/\/[^#\s]+)#[^\s]*/i' => '$1#[REDACTED]',

            // Secret URLs: /s/{token}
            '#(/s/)[A-Za-z0-9_-]{20,}#' => '$1[TOKEN]',

            // API secret URLs: /api/secrets/{token}
            '#(/api/secrets/)[A-Za-z0-9_-]{20,}#' => '$1[TOKEN]',

            // Admin verify URLs: /admin/verify/{token}
            '#(/admin/verify/)[A-Za-z0-9_-]{20,}#' => '$1[TOKEN]',

            // Superadmin verify URLs: /superadmin/verify/{token}
            '#(/superadmin/verify/)[A-Za-z0-9_-]{20,}#' => '$1[TOKEN]',

            // Base64-encoded data (potential ciphertext) - very long unbroken strings
            // Threshold at 200 to avoid redacting stack traces or long class names
            '/[A-Za-z0-9+\/=_-]{200,}/' => '[REDACTED_DATA]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value) ?? $value;
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $lowerKey = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($lowerKey, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
