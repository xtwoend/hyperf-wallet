<?php

namespace Xtwoend\Wallet\Traits;

use function make;
use Xtwoend\Wallet\Exceptions\AmountInvalid;
use Xtwoend\Wallet\Exceptions\BalanceIsEmpty;
use Xtwoend\Wallet\Exceptions\InsufficientFunds;
use Xtwoend\Wallet\Interfaces\Customer;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Interfaces\Product;
use Xtwoend\Wallet\Interfaces\Wallet;
use Xtwoend\Wallet\Models\Transfer;
use Xtwoend\Wallet\Objects\Bring;
use Xtwoend\Wallet\Services\CommonService;
use Xtwoend\Wallet\Services\DbService;
use Xtwoend\Wallet\Services\LockService;
use Xtwoend\Wallet\Services\WalletService;
use Throwable;

/**
 * Trait HasGift.
 */
trait HasGift
{
    /**
     * Give the goods safely.
     *
     * @param Wallet $to
     * @param Product $product
     * @param bool $force
     *
     * @return Transfer|null
     */
    public function safeGift(Wallet $to, Product $product, bool $force = null): ?Transfer
    {
        try {
            return $this->gift($to, $product, $force);
        } catch (Throwable $throwable) {
            return null;
        }
    }

    /**
     * From this moment on, each user (wallet) can give
     * the goods to another user (wallet).
     * This functionality can be organized for gifts.
     *
     * @param Wallet $to
     * @param Product $product
     * @param bool $force
     *
     * @return Transfer
     *
     * @throws AmountInvalid
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws Throwable
     */
    public function gift(Wallet $to, Product $product, bool $force = null): Transfer
    {
        return make(LockService::class)->lock($this, __FUNCTION__, function () use ($to, $product, $force): Transfer {
            /**
             * Who's giving? Let's call him Santa Claus.
             * @var Customer $santa
             */
            $santa = $this;

            /**
             * Unfortunately,
             * I think it is wrong to make the "assemble" method public.
             * That's why I address him like this!
             */
            return make(DbService::class)->transaction(static function () use ($santa, $to, $product, $force): Transfer {
                $math = make(Mathable::class);
                $discount = make(WalletService::class)->discount($santa, $product);
                $amount = $math->sub($product->getAmountProduct($santa), $discount);
                $meta = $product->getMetaProduct();
                $fee = make(WalletService::class)
                    ->fee($product, $amount);

                $commonService = make(CommonService::class);

                /**
                 * Santa pays taxes.
                 */
                if (! $force) {
                    $commonService->verifyWithdraw($santa, $math->add($amount, $fee));
                }

                $withdraw = $commonService->forceWithdraw($santa, $math->add($amount, $fee), $meta);
                $deposit = $commonService->deposit($product, $amount, $meta);

                $from = make(WalletService::class)
                    ->getWallet($to);

                $transfers = $commonService->assemble([
                    make(Bring::class)
                        ->setStatus(Transfer::STATUS_GIFT)
                        ->setDiscount($discount)
                        ->setDeposit($deposit)
                        ->setWithdraw($withdraw)
                        ->setFrom($from)
                        ->setTo($product),
                ]);

                return current($transfers);
            });
        });
    }

    /**
     * to give force).
     *
     * @param Wallet $to
     * @param Product $product
     *
     * @return Transfer
     *
     * @throws AmountInvalid
     * @throws Throwable
     */
    public function forceGift(Wallet $to, Product $product): Transfer
    {
        return $this->gift($to, $product, true);
    }
}
