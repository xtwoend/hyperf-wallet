<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Interfaces\WalletFloat;
use Xtwoend\Wallet\Traits\HasWalletFloat;
use Xtwoend\Wallet\Traits\HasWallets;
use Hyperf\DbConnection\Model\Model;

/**
 * Class User.
 *
 * @property string $name
 * @property string $email
 */
class UserMulti extends Model implements Wallet, WalletFloat
{
    use HasWalletFloat, HasWallets;

    /**
     * @return string
     */
    public function getTable(): string
    {
        return 'users';
    }
}
