<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NoFeatureInfoException extends HttpException
{
    public function __construct( string $message = null, \Exception $previous = null, array $headers = array(), ?int $code = 0)
    {
        parent::__construct(403, $message, $previous, $headers, $code);
    }
}
