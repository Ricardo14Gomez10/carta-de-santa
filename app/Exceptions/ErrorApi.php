<?php

namespace App\Exceptions;

use RuntimeException;

class ErrorApi extends RuntimeException
{
    public function __construct(public int $status, public string $codigo, string $mensaje)
    {
        parent::__construct($mensaje);
    }
}
