<?php

namespace App\Services;

final class ContentSanitizer
{
    public function redact(string $content): string
    {
        $content = preg_replace('/[\w.%+-]+@[\w.-]+\.[A-Za-z]{2,}/u', '[email redacted]', $content) ?? $content;
        $content = preg_replace('/\b(?:phone|mobile|telephone|tel|whatsapp)\s*[:=]?\s*\+?[\d().\s-]{7,20}\b/iu', '[phone number redacted]', $content) ?? $content;
        $content = preg_replace('/\b[A-Z]{2}\d{2}(?:[ -]?[A-Z0-9]){11,30}\b/iu', '[account identifier redacted]', $content) ?? $content;
        $content = preg_replace('/(?<!\d)(?:\d[ -]?){13,19}(?!\d)/u', '[payment card number redacted]', $content) ?? $content;
        $content = preg_replace('/\b(password|passphrase|secret|token|api[_-]?key|access[_-]?key)\s*[:=]\s*[^\s,;]+/iu', '$1: [redacted]', $content) ?? $content;

        return $content;
    }

    /** @param array<string|int, mixed> $metadata
     *  @return array<string|int, mixed>
     */
    public function redactMetadata(array $metadata): array
    {
        $redactValue = function (mixed $value) use (&$redactValue): mixed {
            if (is_string($value)) {
                return $this->redact($value);
            }

            if (is_array($value)) {
                foreach ($value as $key => $nestedValue) {
                    $value[$key] = $redactValue($nestedValue);
                }

                return $value;
            }

            return is_scalar($value) || $value === null ? $value : null;
        };

        return $redactValue($metadata);
    }
}
