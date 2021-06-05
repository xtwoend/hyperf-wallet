<?php

namespace Xtwoend\Wallet\Exceptions;

use Throwable;

class BalanceIsEmpty extends InsufficientFunds
{
    public function __construct($message = null, $code = 102, Throwable $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
}
