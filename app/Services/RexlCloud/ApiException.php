<?php

namespace App\Services\RexlCloud;

use RuntimeException;

/**
 * The RestModule answered with an error (4xx/5xx other than 401).
 */
class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?string $error = null,
    ) {
        parent::__construct($message, $status);
    }
}
