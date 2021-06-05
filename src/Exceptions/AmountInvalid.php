<?php

namespace Xtwoend\Wallet\Exceptions;

use Throwable;
use InvalidArgumentException;

class AmountInvalid extends InvalidArgumentException
{
    public function __construct($message = null, $code = 101, Throwable $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
}
