<?php

namespace Xtwoend\Wallet\Traits;

use Xtwoend\Wallet\Exceptions\BalanceIsEmpty;
use Xtwoend\Wallet\Exceptions\ConfirmedInvalid;
use Xtwoend\Wallet\Exceptions\InsufficientFunds;
use Xtwoend\Wallet\Exceptions\WalletOwnerInvalid;
use Xtwoend\Wallet\Interfaces\Confirmable;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Models\Transaction;
use Xtwoend\Wallet\Services\CommonService;
use Xtwoend\Wallet\Services\DbService;
use Xtwoend\Wallet\Services\LockService;
use Xtwoend\Wallet\Services\WalletService;

trait CanConfirm
{
    /**
     * @param Transaction $transaction
     *
     * @return bool
     *
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws ConfirmedInvalid
     * @throws WalletOwnerInvalid
     */
    public function confirm(Transaction $transaction): bool
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($transaction) {
            /** @var Wallet|Confirmable $self */
            $self = $this;

            return make(DbService::class)->transaction(static function () use ($self, $transaction) {
                $wallet = make(WalletService::class)->getWallet($self);
                if (! $wallet->refreshBalance()) {
                    return false;
                }

                if ($transaction->type === Transaction::TYPE_WITHDRAW) {
                    make(CommonService::class)->verifyWithdraw(
                        $wallet,
                        make(Mathable::class)->abs($transaction->amount)
                    );
                }

                return $self->forceConfirm($transaction);
            });
        });
    }

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function safeConfirm(Transaction $transaction): bool
    {
        try {
            return $this->confirm($transaction);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * Removal of confirmation (forced), use at your own peril and risk.
     *
     * @param Transaction $transaction
     *
     * @return bool
     *
     * @throws ConfirmedInvalid
     */
    public function resetConfirm(Transaction $transaction): bool
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($transaction) {
            /** @var Wallet $self */
            $self = $this;

            return make(DbService::class)->transaction(static function () use ($self, $transaction) {
                $wallet = make(WalletService::class)->getWallet($self);
                if (! $wallet->refreshBalance()) {
                    return false;
                }

                if (! $transaction->confirmed) {
                    throw new ConfirmedInvalid(trans('wallet::errors.unconfirmed_invalid'));
                }

                $mathService = make(Mathable::class);
                $negativeAmount = $mathService->negative($transaction->amount);

                return $transaction->update(['confirmed' => false]) &&

                    // update balance
                    make(CommonService::class)
                        ->addBalance($wallet, $negativeAmount);
            });
        });
    }

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function safeResetConfirm(Transaction $transaction): bool
    {
        try {
            return $this->resetConfirm($transaction);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @param Transaction $transaction
     *
     * @return bool
     *
     * @throws ConfirmedInvalid
     * @throws WalletOwnerInvalid
     */
    public function forceConfirm(Transaction $transaction): bool
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($transaction) {
            /** @var Wallet $self */
            $self = $this;

            return make(DbService::class)->transaction(static function () use ($self, $transaction) {
                $wallet = make(WalletService::class)
                    ->getWallet($self);

                if ($transaction->confirmed) {
                    throw new ConfirmedInvalid(trans('wallet::errors.confirmed_invalid'));
                }

                if ($wallet->getKey() !== $transaction->wallet_id) {
                    throw new WalletOwnerInvalid(trans('wallet::errors.owner_invalid'));
                }

                return $transaction->update(['confirmed' => true]) &&

                    // update balance
                    make(CommonService::class)
                        ->addBalance($wallet, $transaction->amount);
            });
        });
    }
}
