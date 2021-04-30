<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Traits\HasWallet;
use Hyperf\DbConnection\Model\Model;

/**
 * Class User.
 *
 * @property string $name
 * @property string $email
 */
class User extends Model implements Wallet
{
    use HasWallet;

    /**
     * @var array
     */
    protected $fillable = ['name', 'email'];
}
