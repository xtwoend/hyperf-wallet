<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Traits\HasWallets;

class ItemWallet extends Item
{
    use HasWallets;

    /**
     * @return string
     */
    public function getTable(): string
    {
        return 'items';
    }
}
