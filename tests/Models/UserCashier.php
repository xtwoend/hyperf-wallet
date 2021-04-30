<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Traits\HasWallets;
use Xtwoend\Wallet\Traits\MorphOneWallet;
use Hyperf\DbConnection\Model\Model;
use Laravel\Cashier\Billable;

/**
 * Class User.
 *
 * @property string $name
 * @property string $email
 */
class UserCashier extends Model
{
    use Billable, HasWallets, MorphOneWallet;

    /**
     * @return string
     */
    public function getTable(): string
    {
        return 'users';
    }
}
