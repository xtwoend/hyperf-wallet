<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Interfaces\Customer;
use Xtwoend\Wallet\Interfaces\Discount;
use Xtwoend\Wallet\Services\WalletService;

class ItemDiscount extends Item implements Discount
{
    /**
     * @return string
     */
    public function getTable(): string
    {
        return 'items';
    }

    /**
     * @param Customer $customer
     * @return int
     */
    public function getPersonalDiscount(Customer $customer): int
    {
        return app(WalletService::class)
            ->getWallet($customer)
            ->holder_id;
    }
}
