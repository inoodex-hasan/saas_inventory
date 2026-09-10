<?php

namespace App\Exceptions;

use Exception;

class InvalidStockOperationException extends Exception
{
    public function __construct(
        string $message = "Invalid stock operation requested.",
        int $code = 400,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
