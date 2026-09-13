<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class BureauIndisponivelException extends RuntimeException
{
    public function __construct(
        public readonly int $analiseId,
        string $message,
        Throwable $previous,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
