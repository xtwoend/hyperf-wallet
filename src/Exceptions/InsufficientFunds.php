<?php

namespace Xtwoend\Wallet\Exceptions;

use Throwable;
use LogicException;

class InsufficientFunds extends LogicException
{
    public function __construct($message = null, $code = 105, Throwable $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
}
