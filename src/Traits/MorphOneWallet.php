<?php

namespace Xtwoend\Wallet\Traits;

use Xtwoend\Wallet\Models\Wallet as WalletModel;
use Hyperf\Database\Model\Relations\MorphOne;

/**
 * Trait MorphOneWallet.
 *
 * @property-read WalletModel $wallet
 */
trait MorphOneWallet
{
    /**
     * Get default Wallet
     * this method is used for Eager Loading.
     *
     * @return MorphOne
     */
    public function wallet(): MorphOne
    {
        return ($this instanceof WalletModel ? $this->holder : $this)
            ->morphOne(config('wallet.wallet.model', WalletModel::class), 'holder')
            ->where('slug', config('wallet.wallet.default.slug', 'default'))
            ->withDefault(array_merge(config('wallet.wallet.creating', []), [
                'name' => config('wallet.wallet.default.name', 'Default Wallet'),
                'slug' => config('wallet.wallet.default.slug', 'default'),
                'meta' => config('wallet.wallet.default.meta', []),
                'balance' => 0,
            ]));
    }
}
