<?php

namespace Xtwoend\Wallet\Exceptions;

use Throwable;
use InvalidArgumentException;

class WalletOwnerInvalid extends InvalidArgumentException
{
    public function __construct($message = null, $code = 107, Throwable $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
}
