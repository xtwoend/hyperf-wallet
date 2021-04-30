<?php

namespace Xtwoend\Wallet\Traits;

use Xtwoend\Wallet\Exceptions\AmountInvalid;
use Xtwoend\Wallet\Exceptions\BalanceIsEmpty;
use Xtwoend\Wallet\Exceptions\InsufficientFunds;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Models\Transaction;
use Xtwoend\Wallet\Models\Transfer;
use Xtwoend\Wallet\Services\WalletService;
use Throwable;

/**
 * Trait HasWalletFloat.
 *
 *
 * @property-read float $balanceFloat
 */
trait HasWalletFloat
{
    use HasWallet;

    /**
     * @param float|string $amount
     * @param array|null $meta
     * @param bool $confirmed
     *
     * @return Transaction
     *
     * @throws AmountInvalid
     * @throws Throwable
     */
    public function forceWithdrawFloat($amount, ?array $meta = null, bool $confirmed = true): Transaction
    {
        /** @var Wallet $this */
        $math = make(Mathable::class);
        $decimalPlacesValue = make(WalletService::class)->decimalPlacesValue($this);
        $decimalPlaces = make(WalletService::class)->decimalPlaces($this);
        $result = $math->round($math->mul($amount, $decimalPlaces, $decimalPlacesValue));

        return $this->forceWithdraw($result, $meta, $confirmed);
    }

    /**
     * @param float|string $amount
     * @param array|null $meta
     * @param bool $confirmed
     *
     * @return Transaction
     *
     * @throws AmountInvalid
     * @throws Throwable
     */
    public function depositFloat($amount, ?array $meta = null, bool $confirmed = true): Transaction
    {
        /** @var Wallet $this */
        $math = make(Mathable::class);
        $decimalPlacesValue = make(WalletService::class)->decimalPlacesValue($this);
        $decimalPlaces = make(WalletService::class)->decimalPlaces($this);
        $result = $math->round($math->mul($amount, $decimalPlaces, $decimalPlacesValue));

        return $this->deposit($result, $meta, $confirmed);
    }

    /**
     * @param float|string $amount
     * @param array|null $meta
     * @param bool $confirmed
     *
     * @return Transaction
     *
     * @throws AmountInvalid
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws Throwable
     */
    public function withdrawFloat($amount, ?array $meta = null, bool $confirmed = true): Transaction
    {
        /** @var Wallet $this */
        $math = make(Mathable::class);
        $decimalPlacesValue = make(WalletService::class)->decimalPlacesValue($this);
        $decimalPlaces = make(WalletService::class)->decimalPlaces($this);
        $result = $math->round($math->mul($amount, $decimalPlaces, $decimalPlacesValue));

        return $this->withdraw($result, $meta, $confirmed);
    }

    /**
     * @param float|string $amount
     *
     * @return bool
     */
    public function canWithdrawFloat($amount): bool
    {
        /** @var Wallet $this */
        $math = make(Mathable::class);
        $decimalPlacesValue = make(WalletService::class)->decimalPlacesValue($this);
        $decimalPlaces = make(WalletService::class)->decimalPlaces($this);
        $result = $math->round($math->mul($amount, $decimalPlaces, $decimalPlacesValue));

        return $this->canWithdraw($result);
    }

    /**
     * @param Wallet $wallet
     * @param float $amount
     * @param array|null $meta
     *
     * @return Transfer
     *
     * @throws AmountInvalid
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws Throwable
     */
    public function transferFloat(Wallet $wallet, $amount, ?array $meta = null): Transfer
    {
        /** @var Wallet $this */
        $math = make(Mathable::class);
        $decimalPlacesValue = make(WalletService::class)->decimalPlacesValue($this);
        $decimalPlaces = make(WalletService::class)->decimalPlaces($this);
        $result = $math->round($math->mul($amount, $decimalPlaces, $decimalPlacesValue));

        return $this->transfer($wallet, $result, $meta);
    }

    /**
     * @param Wallet $wallet
     * @param float $amount
     * @param array|null $meta
     *
     * @return Transfer|null
     */
    public function safeTransferFloat(Wallet $wallet, $amount, ?array $meta = null): ?Transfer
    {
        /** @var Wallet $this */
        $math = make(Mathable::class);
        $decimalPlacesValue = make(WalletService::class)->decimalPlacesValue($this);
        $decimalPlaces = make(WalletService::class)->decimalPlaces($this);
        $result = $math->round($math->mul($amount, $decimalPlaces, $decimalPlacesValue));

        return $this->safeTransfer($wallet, $result, $meta);
    }

    /**
     * @param Wallet $wallet
     * @param float|string $amount
     * @param array|null $meta
     *
     * @return Transfer
     *
     * @throws AmountInvalid
     * @throws Throwable
     */
    public function forceTransferFloat(Wallet $wallet, $amount, ?array $meta = null): Transfer
    {
        /** @var Wallet $this */
        $math = make(Mathable::class);
        $decimalPlacesValue = make(WalletService::class)->decimalPlacesValue($this);
        $decimalPlaces = make(WalletService::class)->decimalPlaces($this);
        $result = $math->round($math->mul($amount, $decimalPlaces, $decimalPlacesValue));

        return $this->forceTransfer($wallet, $result, $meta);
    }

    /**
     * @return int|float|string
     */
    public function getBalanceFloatAttribute()
    {
        /** @var Wallet $this */
        $math = make(Mathable::class);
        $decimalPlacesValue = make(WalletService::class)->decimalPlacesValue($this);
        $decimalPlaces = make(WalletService::class)->decimalPlaces($this);

        return $math->div($this->balance, $decimalPlaces, $decimalPlacesValue);
    }
}
