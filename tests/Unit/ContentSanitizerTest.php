<?php

namespace Tests\Unit;

use App\Services\ContentSanitizer;
use PHPUnit\Framework\TestCase;

final class ContentSanitizerTest extends TestCase
{
    public function test_it_redacts_sensitive_values_in_text_and_nested_metadata(): void
    {
        $sanitizer = new ContentSanitizer;

        $text = $sanitizer->redact('Contact test@example.test; token=secret-value');
        $metadata = $sanitizer->redactMetadata([
            'message' => 'Email test@example.test',
            'nested' => ['note' => 'password=not-labelled-secret'],
            'count' => 2,
        ]);

        $this->assertStringNotContainsString('test@example.test', $text);
        $this->assertStringNotContainsString('secret-value', $text);
        $this->assertSame('Email [email redacted]', $metadata['message']);
        $this->assertSame('password: [redacted]', $metadata['nested']['note']);
        $this->assertSame(2, $metadata['count']);
    }
}
