<?php

namespace Xtwoend\Wallet\Exceptions;

use Throwable;
use LogicException;

class DifferentCurrency extends LogicException
{
    public function __construct($message = null, $code = 104, Throwable $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
}