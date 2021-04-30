<?php

namespace Xtwoend\Wallet\Services;

use function make;
use Xtwoend\Wallet\Exceptions\AmountInvalid;
use Xtwoend\Wallet\Exceptions\BalanceIsEmpty;
use Xtwoend\Wallet\Exceptions\InsufficientFunds;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Interfaces\Storable;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Models\Transaction;
use Xtwoend\Wallet\Models\Transfer;
use Xtwoend\Wallet\Models\Wallet as WalletModel;
use Xtwoend\Wallet\Objects\Bring;
use Xtwoend\Wallet\Objects\Operation;
use Xtwoend\Wallet\Traits\HasWallet;
use function compact;
use function max;
use Throwable;

class CommonService
{
    /**
     * @param Wallet $from
     * @param Wallet $to
     * @param int|string $amount
     * @param array|null $meta
     * @param string $status
     *
     * @return Transfer
     *
     * @throws AmountInvalid
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws Throwable
     */
    public function transfer(Wallet $from, Wallet $to, $amount, ?array $meta = null, string $status = Transfer::STATUS_TRANSFER): Transfer
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($from, $to, $amount, $meta, $status) {
            $math = make(Mathable::class);
            $discount = make(WalletService::class)->discount($from, $to);
            $newAmount = max(0, $math->sub($amount, $discount));
            $fee = make(WalletService::class)->fee($to, $newAmount);
            $this->verifyWithdraw($from, $math->add($newAmount, $fee));

            return $this->forceTransfer($from, $to, $amount, $meta, $status);
        });
    }

    /**
     * @param Wallet $from
     * @param Wallet $to
     * @param int|string $amount
     * @param array|null $meta
     * @param string $status
     *
     * @return Transfer
     *
     * @throws AmountInvalid
     * @throws Throwable
     */
    public function forceTransfer(Wallet $from, Wallet $to, $amount, ?array $meta = null, string $status = Transfer::STATUS_TRANSFER): Transfer
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($from, $to, $amount, $meta, $status) {
            $math = make(Mathable::class);
            $from = make(WalletService::class)->getWallet($from);
            $discount = make(WalletService::class)->discount($from, $to);
            $fee = make(WalletService::class)->fee($to, $amount);

            $amount = max(0, $math->sub($amount, $discount));
            $placesValue = make(WalletService::class)->decimalPlacesValue($from);
            $withdraw = $this->forceWithdraw($from, $math->add($amount, $fee, $placesValue), $meta);
            $deposit = $this->deposit($to, $amount, $meta);

            $transfers = $this->multiBrings([
                make(Bring::class)
                    ->setStatus($status)
                    ->setDeposit($deposit)
                    ->setWithdraw($withdraw)
                    ->setDiscount($discount)
                    ->setFrom($from)
                    ->setTo($to),
            ]);

            return current($transfers);
        });
    }

    /**
     * @param Wallet $wallet
     * @param int|string $amount
     * @param array|null $meta
     * @param bool $confirmed
     *
     * @return Transaction
     *
     * @throws AmountInvalid
     */
    public function forceWithdraw(Wallet $wallet, $amount, ?array $meta, bool $confirmed = true): Transaction
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($wallet, $amount, $meta, $confirmed) {
            $walletService = make(WalletService::class);
            $walletService->checkAmount($amount);

            /**
             * @var WalletModel $wallet
             */
            $wallet = $walletService->getWallet($wallet);

            $mathService = make(Mathable::class);
            $transactions = $this->multiOperation($wallet, [
                make(Operation::class)
                    ->setType(Transaction::TYPE_WITHDRAW)
                    ->setConfirmed($confirmed)
                    ->setAmount($mathService->negative($amount))
                    ->setMeta($meta),
            ]);

            return current($transactions);
        });
    }

    /**
     * @param Wallet $wallet
     * @param int|string $amount
     * @param array|null $meta
     * @param bool $confirmed
     *
     * @return Transaction
     *
     * @throws AmountInvalid
     */
    public function deposit(Wallet $wallet, $amount, ?array $meta, bool $confirmed = true): Transaction
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($wallet, $amount, $meta, $confirmed) {
            $walletService = make(WalletService::class);
            $walletService->checkAmount($amount);

            /**
             * @var WalletModel $wallet
             */
            $wallet = $walletService->getWallet($wallet);

            $transactions = $this->multiOperation($wallet, [
                make(Operation::class)
                    ->setType(Transaction::TYPE_DEPOSIT)
                    ->setConfirmed($confirmed)
                    ->setAmount($amount)
                    ->setMeta($meta),
            ]);

            return current($transactions);
        });
    }

    /**
     * @param Wallet $wallet
     * @param int|string $amount
     * @param bool $allowZero
     *
     * @return void
     *
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     */
    public function verifyWithdraw(Wallet $wallet, $amount, bool $allowZero = null): void
    {
        /**
         * @var HasWallet $wallet
         */
        if ($amount && ! $wallet->balance) {
            throw new BalanceIsEmpty(trans('wallet::errors.wallet_empty'));
        }

        if (! $wallet->canWithdraw($amount, $allowZero)) {
            throw new InsufficientFunds(trans('wallet::errors.insufficient_funds'));
        }
    }

    /**
     * Create Operation without DB::transaction.
     *
     * @param Wallet $self
     * @param Operation[] $operations
     *
     * @return array
     */
    public function multiOperation(Wallet $self, array $operations): array
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($self, $operations) {
            $amount = 0;
            $objects = [];
            $math = make(Mathable::class);
            foreach ($operations as $operation) {
                if ($operation->isConfirmed()) {
                    $amount = $math->add($amount, $operation->getAmount());
                }

                $objects[] = $operation
                    ->setWallet($self)
                    ->create();
            }

            $this->addBalance($self, $amount);

            return $objects;
        });
    }

    /**
     * Create Bring with DB::transaction.
     *
     * @param Bring[] $brings
     *
     * @return array
     *
     * @throws
     */
    public function assemble(array $brings): array
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($brings) {
            $self = $this;

            return make(DbService::class)->transaction(static function () use ($self, $brings) {
                return $self->multiBrings($brings);
            });
        });
    }

    /**
     * Create Bring without DB::transaction.
     *
     * @param array $brings
     *
     * @return array
     */
    public function multiBrings(array $brings): array
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($brings) {
            $objects = [];
            foreach ($brings as $bring) {
                $objects[] = $bring->create();
            }

            return $objects;
        });
    }

    /**
     * @param Wallet $wallet
     * @param int|string $amount
     *
     * @return bool
     *
     * @throws
     */
    public function addBalance(Wallet $wallet, $amount): bool
    {
        return make(LockService::class)->lock($this, __FUNCTION__, static function () use ($wallet, $amount) {
            /**
             * @var WalletModel $wallet
             */
            $balance = make(Storable::class)
                ->incBalance($wallet, $amount);

            try {
                $result = $wallet->newQuery()
                    ->whereKey($wallet->getKey())
                    ->update(compact('balance'));
            } catch (Throwable $throwable) {
                make(Storable::class)
                    ->setBalance($wallet, $wallet->getAvailableBalance());

                throw $throwable;
            }

            if ($result) {
                $wallet->fill(compact('balance'))
                    ->syncOriginalAttributes('balance');
            } else {
                make(Storable::class)
                    ->setBalance($wallet, $wallet->getAvailableBalance());
            }

            return $result;
        });
    }
}
