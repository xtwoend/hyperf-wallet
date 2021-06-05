<?php

namespace Xtwoend\Wallet\Exceptions;

use Throwable;
use InvalidArgumentException;

class ConfirmedInvalid extends InvalidArgumentException
{
    public function __construct($message = null, $code = 103, Throwable $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
}
