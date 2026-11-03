<?php

namespace Tests\Unit;

use App\Services\JsonInputReader;
use PHPUnit\Framework\TestCase;

final class JsonInputReaderTest extends TestCase
{
    public function test_it_decodes_json_objects_into_associative_arrays(): void
    {
        $reader = new JsonInputReader;

        $this->assertSame([
            'title' => 'Sample request',
            'metadata' => ['source' => 'test'],
        ], $reader->decode('{"title":"Sample request","metadata":{"source":"test"}}'));
    }

    public function test_invalid_json_decodes_to_null(): void
    {
        $reader = new JsonInputReader;

        $this->assertNull($reader->decode('{invalid json'));
    }
}
