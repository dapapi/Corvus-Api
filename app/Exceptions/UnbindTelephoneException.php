<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnbindTelephoneException extends HttpException {
    public function __construct(string $message = null, \Exception $previous = null, ?int $code = 0, array $headers = array()) {

        parent::__construct(441, $message, $previous, $headers, $code);
    }
}
