<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Interfaces\WalletFloat;
use Xtwoend\Wallet\Traits\HasWalletFloat;
use Hyperf\DbConnection\Model\Model;

/**
 * Class UserFloat.
 *
 * @property string $name
 * @property string $email
 */
class UserFloat extends Model implements Wallet, WalletFloat
{
    use HasWalletFloat;

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
