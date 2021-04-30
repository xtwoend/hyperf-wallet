<?php

namespace Xtwoend\Wallet\Services;

use function make;
use Xtwoend\Wallet\Exceptions\AmountInvalid;
use Xtwoend\Wallet\Interfaces\Customer;
use Xtwoend\Wallet\Interfaces\Discount;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Interfaces\MinimalTaxable;
use Xtwoend\Wallet\Interfaces\Storable;
use Xtwoend\Wallet\Interfaces\Taxable;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Models\Wallet as WalletModel;
use Xtwoend\Wallet\Traits\HasWallet;
use Throwable;

class WalletService
{
    /**
     * @param Wallet $customer
     * @param Wallet $product
     *
     * @return int
     */
    public function discount(Wallet $customer, Wallet $product): int
    {
        if ($customer instanceof Customer && $product instanceof Discount) {
            return (int) $product->getPersonalDiscount($customer);
        }

        // without discount
        return 0;
    }

    /**
     * @param Wallet $object
     *
     * @return int
     */
    public function decimalPlacesValue(Wallet $object): int
    {
        return $this->getWallet($object)->decimal_places ?: 2;
    }

    /**
     * @param Wallet $object
     *
     * @return string
     */
    public function decimalPlaces(Wallet $object): string
    {
        return make(Mathable::class)
            ->pow(10, $this->decimalPlacesValue($object));
    }

    /**
     * Consider the fee that the system will receive.
     *
     * @param Wallet $wallet
     * @param int|string $amount
     *
     * @return float|int
     */
    public function fee(Wallet $wallet, $amount)
    {
        $fee = 0;
        $math = make(Mathable::class);
        if ($wallet instanceof Taxable) {
            $placesValue = $this->decimalPlacesValue($wallet);
            $fee = $math->floor(
                $math->div(
                    $math->mul($amount, $wallet->getFeePercent(), 0),
                    100,
                    $placesValue
                )
            );
        }

        /**
         * Added minimum commission condition.
         *
         * @see https://github.com/Xtwoend/laravel-wallet/issues/64#issuecomment-514483143
         */
        if ($wallet instanceof MinimalTaxable) {
            $minimal = $wallet->getMinimalFee();
            if (make(Mathable::class)->compare($fee, $minimal) === -1) {
                $fee = $minimal;
            }
        }

        return $fee;
    }

    /**
     * The amount of checks for errors.
     *
     * @param int|string $amount
     *
     * @throws AmountInvalid
     */
    public function checkAmount($amount): void
    {
        if (make(Mathable::class)->compare($amount, 0) === -1) {
            throw new AmountInvalid(trans('wallet::errors.price_positive'));
        }
    }

    /**
     * @param Wallet $object
     * @param bool $autoSave
     *
     * @return WalletModel
     */
    public function getWallet(Wallet $object, bool $autoSave = true): WalletModel
    {
        /**
         * @var WalletModel $wallet
         */
        $wallet = $object;

        if (! ($object instanceof WalletModel)) {
            /**
             * @var HasWallet $object
             */
            $wallet = $object->wallet;
        }

        if ($autoSave) {
            $wallet->exists or $wallet->save();
        }

        return $wallet;
    }

    /**
     * @param WalletModel $wallet
     *
     * @return bool
     */
    public function refresh(WalletModel $wallet): bool
    {
        return make(LockService::class)->lock($this, __FUNCTION__, static function () use ($wallet) {
            $math = make(Mathable::class);
            make(Storable::class)->getBalance($wallet);
            $whatIs = $wallet->balance;
            $balance = $wallet->getAvailableBalance();
            $wallet->balance = $balance;

            return make(Storable::class)->setBalance($wallet, $balance) &&
                (! $math->compare($whatIs, $balance) || $wallet->save());
        });
    }

    /**
     * @param WalletModel $wallet
     * @param array|null $meta
     *
     * @return void
     *
     * @throws Throwable
     */
    public function adjustment(WalletModel $wallet, ?array $meta = null): void
    {
        make(DbService::class)->transaction(function () use ($wallet, $meta) {
            $math = make(Mathable::class);
            make(Storable::class)->getBalance($wallet);
            $adjustmentBalance = $wallet->balance;
            $wallet->refreshBalance();
            $difference = $math->sub($wallet->balance, $adjustmentBalance);

            switch ($math->compare($difference, 0)) {
                case -1:
                    $wallet->deposit($math->abs($difference), $meta);
                    break;
                case 1:
                    $wallet->forceWithdraw($math->abs($difference), $meta);
                    break;
            }
        });
    }
}
