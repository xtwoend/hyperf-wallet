<?php

namespace Xtwoend\Wallet\Test\Models;

use Xtwoend\Wallet\Interfaces\Customer;
use Xtwoend\Wallet\Interfaces\Product;
use Xtwoend\Wallet\Models\Transfer;
use Xtwoend\Wallet\Models\Wallet;
use Xtwoend\Wallet\Services\WalletService;
use Xtwoend\Wallet\Traits\HasWallet;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Database\Model\Relations\MorphMany;

/**
 * Class Item.
 *
 * @property string $name
 * @property int $quantity
 * @property int $price
 */
class Item extends Model implements Product
{
    use HasWallet;

    /**
     * @var array
     */
    protected $fillable = ['name', 'quantity', 'price'];

    /**
     * @param Customer $customer
     * @param int $quantity
     * @param bool $force
     *
     * @return bool
     */
    public function canBuy(Customer $customer, int $quantity = 1, bool $force = null): bool
    {
        $result = $this->quantity >= $quantity;

        if ($force) {
            return $result;
        }

        return $result && ! $customer->paid($this);
    }

    /**
     * @param Customer $customer
     * @return float|int
     */
    public function getAmountProduct(Customer $customer)
    {
        /**
         * @var Wallet $wallet
         */
        $wallet = app(WalletService::class)->getWallet($customer);

        return $this->price + $wallet->holder_id;
    }

    /**
     * @return array|null
     */
    public function getMetaProduct(): ?array
    {
        return null;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->getKey();
    }

    /**
     * @param int[] $walletIds
     * @return MorphMany
     */
    public function boughtGoods(array $walletIds): MorphMany
    {
        return $this
            ->morphMany(config('wallet.transfer.model', Transfer::class), 'to')
            ->where('status', Transfer::STATUS_PAID)
            ->where('from_type', config('wallet.wallet.model', Wallet::class))
            ->whereIn('from_id', $walletIds);
    }
}
