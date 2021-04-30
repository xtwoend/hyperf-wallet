<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Interfaces\MinimalTaxable;

class ItemMinTax extends Item implements MinimalTaxable
{
    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return 'items';
    }

    /**
     * {@inheritdoc}
     */
    public function getFeePercent(): float
    {
        return 3;
    }

    /**
     * @return int
     */
    public function getMinimalFee(): int
    {
        return 90;
    }
}
