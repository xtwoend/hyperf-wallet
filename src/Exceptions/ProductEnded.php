<?php

namespace Xtwoend\Wallet\Exceptions;

use Throwable;
use LogicException;

class ProductEnded extends LogicException
{
    public function __construct($message = null, $code = 106, Throwable $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
}
