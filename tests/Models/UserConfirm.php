<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Interfaces\Confirmable;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Traits\CanConfirm;
use Xtwoend\Wallet\Traits\HasWallet;
use Hyperf\DbConnection\Model\Model;

/**
 * Class UserConfirm.
 *
 * @property string $name
 * @property string $email
 */
class UserConfirm extends Model implements Wallet, Confirmable
{
    use HasWallet, CanConfirm;

    /**
     * @var array
     */
    protected $fillable = ['name', 'email'];

    /**
     * @return string
     */
    public function getTable(): string
    {
        return 'users';
    }
}
