<?php

namespace Xtwoend\Wallet\Traits;

use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Models\Transfer;
use Xtwoend\Wallet\Objects\Bring;
use Xtwoend\Wallet\Services\CommonService;
use Xtwoend\Wallet\Services\DbService;
use Xtwoend\Wallet\Services\ExchangeService;
use Xtwoend\Wallet\Services\LockService;
use Xtwoend\Wallet\Services\WalletService;

trait CanExchange
{
    /**
     * {@inheritdoc}
     */
    public function exchange(Wallet $to, $amount, ?array $meta = null): Transfer
    {
        $wallet = make(WalletService::class)
            ->getWallet($this);

        make(CommonService::class)
            ->verifyWithdraw($wallet, $amount);

        return $this->forceExchange($to, $amount, $meta);
    }

    /**
     * {@inheritdoc}
     */
    public function safeExchange(Wallet $to, $amount, ?array $meta = null): ?Transfer
    {
        try {
            return $this->exchange($to, $amount, $meta);
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forceExchange(Wallet $to, $amount, ?array $meta = null): Transfer
    {
        /**
         * @var Wallet $from
         */
        $from = make(WalletService::class)->getWallet($this);

        return make(LockService::class)->lock($this, __FUNCTION__, static function () use ($from, $to, $amount, $meta) {
            return make(DbService::class)->transaction(static function () use ($from, $to, $amount, $meta) {
                $math = make(Mathable::class);
                $rate = make(ExchangeService::class)->rate($from, $to);
                $fee = make(WalletService::class)->fee($to, $amount);
                $meta = array_merge($meta ?? [], [
                    'rate' => make(ExchangeService::class)->getRate($from, $to)
                ]);
                    var_dump($meta);
                $withdraw = make(CommonService::class)
                    ->forceWithdraw($from, $math->add($amount, $fee), $meta);

                $deposit = make(CommonService::class)
                    ->deposit($to, $math->floor($math->mul($amount, $rate, 1)), $meta);

                $transfers = make(CommonService::class)->multiBrings([
                    make(Bring::class)
                        ->setDiscount(0)
                        ->setStatus(Transfer::STATUS_EXCHANGE)
                        ->setDeposit($deposit)
                        ->setWithdraw($withdraw)
                        ->setFrom($from)
                        ->setMeta($meta)
                        ->setFee($fee)
                        ->setTo($to),
                ]);

                return current($transfers);
            });
        });
    }
}
