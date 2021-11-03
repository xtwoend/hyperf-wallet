<?php

namespace Xtwoend\Wallet\Traits;

use function config;
use function array_key_exists;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\MorphMany;
use Xtwoend\Wallet\Models\Wallet as WalletModel;
use Hyperf\Database\Model\ModelNotFoundException;


/**
 * Trait HasWallets
 * To use a trait, you must add HasWallet trait.
 *
 *
 * @property-read Collection|WalletModel[] $wallets
 */
trait HasWallets
{
    /**
     * The variable is used for the cache, so as not to request wallets many times.
     * WalletProxy keeps the money wallets in the memory to avoid errors when you
     * purchase/transfer, etc.
     *
     * @var array
     */
    private $_wallets = [];

    /**
     * @var bool
     */
    private $_loadedWallets;

    /**
     * Get wallet by slug.
     *
     *  $user->wallet->balance // 200
     *  or short recording $user->balance; // 200
     *
     *  $defaultSlug = config('wallet.wallet.default.slug');
     *  $user->getWallet($defaultSlug)->balance; // 200
     *
     *  $user->getWallet('usd')->balance; // 50
     *  $user->getWallet('rub')->balance; // 100
     *
     * @param string $slug
     *
     * @return WalletModel|null
     */
    public function getWallet(string $slug): ?WalletModel
    {
        try {
            return $this->getWalletOrFail($slug);
        } catch (ModelNotFoundException $modelNotFoundException) {
            return null;
        }
    }
    
    /**
     * Undocumented function
     *
     * @param integer $id
     * @return WalletModel|null
     */
    public function getWalletById(int $id): ?WalletModel
    {
        try {
            if (! $this->_loadedWallets && $this->relationLoaded('wallets')) {
                $this->_loadedWallets = true;
                $wallets = $this->getRelation('wallets');
                foreach ($wallets as $wallet) {
                    $this->_wallets[$wallet->slug] = $wallet;
                }
            }else {
                $wallet = $this->wallets()->findOrFail($id);
                if ($wallet) {
                    $slug = $wallet->slug;
                    $this->_wallets[$slug] = $wallet;
                }
            }

            return $this->_wallets[$slug];
        } catch (ModelNotFoundException $modelNotFoundException) {
            return null;
        }
    }

    /**
     * Get wallet by slug.
     *
     *  $user->wallet->balance // 200
     *  or short recording $user->balance; // 200
     *
     *  $defaultSlug = config('wallet.wallet.default.slug');
     *  $user->getWallet($defaultSlug)->balance; // 200
     *
     *  $user->getWallet('usd')->balance; // 50
     *  $user->getWallet('rub')->balance; // 100
     *
     * @param string $slug
     *
     * @return WalletModel
     *
     * @throws ModelNotFoundException
     */
    public function getWalletOrFail(string $slug): WalletModel
    {
        if (! $this->_loadedWallets && $this->relationLoaded('wallets')) {
            $this->_loadedWallets = true;
            $wallets = $this->getRelation('wallets');
            foreach ($wallets as $wallet) {
                $this->_wallets[$wallet->slug] = $wallet;
            }
        }

        if (! array_key_exists($slug, $this->_wallets)) {
            $this->_wallets[$slug] = $this->wallets()
                ->where('slug', $slug)
                ->firstOrFail();
        }

        return $this->_wallets[$slug];
    }

    /**
     * method of obtaining all wallets.
     *
     * @return MorphMany
     */
    public function wallets(): MorphMany
    {
        return $this->morphMany(config('wallet.wallet.model', WalletModel::class), 'holder');
    }

    /**
     * @param array $data
     *
     * @return WalletModel
     */
    public function createWallet(array $data): WalletModel
    {
        /**
         * @var WalletModel $wallet
         */
        $wallet = $this->wallets()->create(array_merge(
            config('wallet.wallet.creating', []),
            $data
        ));

        $this->_wallets[$wallet->slug] = $wallet;

        return $wallet;
    }

    /**
     * The method checks the existence of the wallet.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function hasWallet(string $slug): bool
    {
        return (bool) $this->getWallet($slug);
    }
}
