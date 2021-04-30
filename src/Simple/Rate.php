<?php

namespace Xtwoend\Wallet\Simple;

use Xtwoend\Wallet\Interfaces\Rateable;
use Xtwoend\Wallet\Interfaces\Wallet;

/**
 * Class Rate.
 */
class Rate implements Rateable
{
    /**
     * @var string
     */
    protected $amount;

    /**
     * @var Wallet|\Xtwoend\Wallet\Models\Wallet
     */
    protected $withCurrency;

    /**
     * {@inheritdoc}
     */
    public function withAmount($amount): Rateable
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withCurrency(Wallet $wallet): Rateable
    {
        $this->withCurrency = $wallet;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function convertTo(Wallet $wallet)
    {
        return $this->amount;
    }
}
