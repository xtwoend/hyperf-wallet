<?php

namespace Xtwoend\Wallet\Traits;

use Throwable;
use function make;
use function config;
use Xtwoend\Wallet\Models\Transfer;
use Hyperf\Database\Model\Collection;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Models\Transaction;
use Xtwoend\Wallet\Services\DbService;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Interfaces\Storable;
use Xtwoend\Wallet\Services\CommonService;
use Xtwoend\Wallet\Services\WalletService;
use Xtwoend\Wallet\Exceptions\AmountInvalid;
use Xtwoend\Wallet\Exceptions\BalanceIsEmpty;
use Hyperf\Database\Model\Relations\MorphMany;
use Xtwoend\Wallet\Exceptions\DifferentCurrency;
use Xtwoend\Wallet\Exceptions\InsufficientFunds;
use Xtwoend\Wallet\Models\Wallet as WalletModel;

/**
 * Trait HasWallet.
 *
 *
 * @property-read Collection|WalletModel[] $wallets
 * @property-read int $balance
 */
trait HasWallet
{
    use MorphOneWallet;

    /**
     * The input means in the system.
     *
     * @param int|string $amount
     * @param array|null $meta
     * @param bool $confirmed
     *
     * @return Transaction
     *
     * @throws AmountInvalid
     * @throws Throwable
     */
    public function deposit($amount, ?array $meta = null, bool $confirmed = true): Transaction
    {
        /** @var Wallet $self */
        $self = $this;

        return make(DbService::class)->transaction(static function () use ($self, $amount, $meta, $confirmed) {
            return make(CommonService::class)
                ->deposit($self, $amount, $meta, $confirmed);
        });
    }

    /**
     * Magic laravel framework method, makes it
     *  possible to call property balance.
     *
     * Example:
     *  $user1 = User::first()->load('wallet');
     *  $user2 = User::first()->load('wallet');
     *
     * Without static:
     *  var_dump($user1->balance, $user2->balance); // 100 100
     *  $user1->deposit(100);
     *  $user2->deposit(100);
     *  var_dump($user1->balance, $user2->balance); // 200 200
     *
     * With static:
     *  var_dump($user1->balance, $user2->balance); // 100 100
     *  $user1->deposit(100);
     *  var_dump($user1->balance); // 200
     *  $user2->deposit(100);
     *  var_dump($user2->balance); // 300
     *
     * @return int|float|string
     *
     * @throws Throwable
     */
    public function getBalanceAttribute()
    {
        /** @var Wallet $this */
        return make(Storable::class)->getBalance($this);
    }

    /**
     * all user actions on wallets will be in this method.
     *
     * @return MorphMany
     */
    public function transactions(): MorphMany
    {
        return ($this instanceof WalletModel ? $this->holder : $this)
            ->morphMany(config('wallet.transaction.model', Transaction::class), 'payable');
    }

    /**
     * This method ignores errors that occur when transferring funds.
     *
     * @param Wallet $wallet
     * @param int|string $amount
     * @param array|null $meta
     *
     * @return Transfer|null
     */
    public function safeTransfer(Wallet $wallet, $amount, ?array $meta = null): ?Transfer
    {
        try {
            return $this->transfer($wallet, $amount, $meta);
        } catch (Throwable $throwable) {
            return null;
        }
    }

    /**
     * A method that transfers funds from host to host.
     *
     * @param Wallet $wallet
     * @param int|string $amount
     * @param array|null $meta
     *
     * @return Transfer
     *
     * @throws AmountInvalid
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws Throwable
     */
    public function transfer(Wallet $wallet, $amount, ?array $meta = null): Transfer
    {
        // verify same currency
        $from   = make(WalletService::class)->getWallet($this);
        $to     = $wallet;
        
        $this->verifyCurrency($from, $to);
        
        /** @var $this Wallet */
        make(CommonService::class)->verifyWithdraw($this, $amount);

        return $this->forceTransfer($wallet, $amount, $meta);
    }

    /**
     * Withdrawals from the system.
     *
     * @param int|string $amount
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
    public function withdraw($amount, ?array $meta = null, bool $confirmed = true): Transaction
    {
        /** @var Wallet $this */
        make(CommonService::class)->verifyWithdraw($this, $amount);

        return $this->forceWithdraw($amount, $meta, $confirmed);
    }

    /**
     * Checks if you can withdraw funds.
     *
     * @param int|string $amount
     * @param bool $allowZero
     *
     * @return bool
     */
    public function canWithdraw($amount, bool $allowZero = null): bool
    {
        $math = make(Mathable::class);

        /**
         * Allow to buy for free with a negative balance.
         */
        if ($allowZero && ! $math->compare($amount, 0)) {
            return true;
        }

        return $math->compare($this->balance, $amount) >= 0;
    }

    /**
     * Forced to withdraw funds from system.
     *
     * @param int|string $amount
     * @param array|null $meta
     * @param bool $confirmed
     *
     * @return Transaction
     *
     * @throws AmountInvalid
     * @throws Throwable
     */
    public function forceWithdraw($amount, ?array $meta = null, bool $confirmed = true): Transaction
    {
        /** @var Wallet $self */
        $self = $this;

        return make(DbService::class)->transaction(static function () use ($self, $amount, $meta, $confirmed) {
            return make(CommonService::class)
                ->forceWithdraw($self, $amount, $meta, $confirmed);
        });
    }

    /**
     * the forced transfer is needed when the user does not have the money and we drive it.
     * Sometimes you do. Depends on business logic.
     *
     * @param Wallet $wallet
     * @param int|string $amount
     * @param array|null $meta
     *
     * @return Transfer
     *
     * @throws AmountInvalid
     * @throws Throwable
     */
    public function forceTransfer(Wallet $wallet, $amount, ?array $meta = null): Transfer
    {
        /** @var Wallet $self */
        $self = $this;

        // verify same currency
        $from   = make(WalletService::class)->getWallet($this);
        $to     = $wallet;

        $this->verifyCurrency($from, $to);

        return make(DbService::class)->transaction(static function () use ($self, $amount, $wallet, $meta) {
            return make(CommonService::class)
                ->forceTransfer($self, $wallet, $amount, $meta);
        });
    }

    /**
     * the transfer table is used to confirm the payment
     * this method receives all transfers.
     *
     * @return MorphMany
     */
    public function transfers(): MorphMany
    {
        /** @var Wallet $this */
        return make(WalletService::class)
            ->getWallet($this, false)
            ->morphMany(config('wallet.transfer.model', Transfer::class), 'from');
    }

    protected function verifyCurrency($from, $to)
    {
        if ($from->currency !== $to->currency) {
            throw new DifferentCurrency(trans('wallet::errors.different_currency'));
        }
        return;
    }
}
