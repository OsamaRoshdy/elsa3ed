<?php

namespace App\Services;

use RuntimeException;

class DaftraException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Daftra API is not configured. Set DAFTRA_API_BASE_URL and DAFTRA_API_TOKEN in .env.');
    }
}
