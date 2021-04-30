<?php

namespace Xtwoend\Wallet\Services;

use Xtwoend\Wallet\Interfaces\Rateable;
use Xtwoend\Wallet\Interfaces\Wallet;

class ExchangeService
{
    /**
     * @param Wallet $from
     * @param Wallet $to
     * @return int|float
     */
    public function rate(Wallet $from, Wallet $to)
    {
        return make(Rateable::class)
            ->withAmount(1)
            ->withCurrency($from)
            ->convertTo($to);
    }
}
