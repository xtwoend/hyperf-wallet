<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Interfaces\Customer;
use Xtwoend\Wallet\Traits\CanPay;
use Hyperf\DbConnection\Model\Model;

/**
 * Class User.
 *
 * @property string $name
 * @property string $email
 */
class Buyer extends Model implements Customer
{
    use CanPay;

    /**
     * @return string
     */
    public function getTable(): string
    {
        return 'users';
    }
}
