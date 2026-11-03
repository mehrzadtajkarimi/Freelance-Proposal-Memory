<?php

namespace App\Services;

class JsonInputReader
{
    public function decode(?string $input = null): mixed
    {
        return json_decode($input ?? stream_get_contents(STDIN), true);
    }
}
