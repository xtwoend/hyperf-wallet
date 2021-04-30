<?php

namespace Xtwoend\Wallet\Interfaces;

use Xtwoend\Wallet\Exceptions\BalanceIsEmpty;
use Xtwoend\Wallet\Exceptions\ConfirmedInvalid;
use Xtwoend\Wallet\Exceptions\InsufficientFunds;
use Xtwoend\Wallet\Exceptions\WalletOwnerInvalid;
use Xtwoend\Wallet\Models\Transaction;

interface Confirmable
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
    public function confirm(Transaction $transaction): bool;

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function safeConfirm(Transaction $transaction): bool;

    /**
     * @param Transaction $transaction
     *
     * @return bool
     *
     * @throws ConfirmedInvalid
     */
    public function resetConfirm(Transaction $transaction): bool;

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function safeResetConfirm(Transaction $transaction): bool;

    /**
     * @param Transaction $transaction
     *
     * @return bool
     *
     * @throws ConfirmedInvalid
     * @throws WalletOwnerInvalid
     */
    public function forceConfirm(Transaction $transaction): bool;
}
