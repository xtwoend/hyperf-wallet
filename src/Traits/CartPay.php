<?php

namespace Xtwoend\Wallet\Traits;

use function array_unique;
use Xtwoend\Wallet\Exceptions\ProductEnded;
use Xtwoend\Wallet\Interfaces\Product;
use Xtwoend\Wallet\Models\Transfer;
use Xtwoend\Wallet\Objects\Cart;
use Xtwoend\Wallet\Services\CommonService;
use Xtwoend\Wallet\Services\DbService;
use Xtwoend\Wallet\Services\MetaService;
use function count;
use Hyperf\DbConnection\Model\ModelNotFoundException;
use Throwable;

trait CartPay
{
    use HasWallet;

    /**
     * @param Cart $cart
     * @return Transfer[]
     * @throws
     */
    public function payFreeCart(Cart $cart): array
    {
        if (! $cart->canBuy($this)) {
            throw new ProductEnded(trans('wallet::errors.product_stock'));
        }

        make(CommonService::class)
            ->verifyWithdraw($this, 0, true);

        $self = $this;

        return make(DbService::class)->transaction(static function () use ($self, $cart) {
            $results = [];
            foreach ($cart->getItems() as $product) {
                $results[] = make(CommonService::class)->forceTransfer(
                    $self,
                    $product,
                    0,
                    make(MetaService::class)->getMeta($cart, $product),
                    Transfer::STATUS_PAID
                );
            }

            return $results;
        });
    }

    /**
     * @param Cart $cart
     * @param bool $force
     * @return Transfer[]
     */
    public function safePayCart(Cart $cart, bool $force = null): array
    {
        try {
            return $this->payCart($cart, $force);
        } catch (Throwable $throwable) {
            return [];
        }
    }

    /**
     * @param Cart $cart
     * @param bool $force
     * @return Transfer[]
     * @throws
     */
    public function payCart(Cart $cart, bool $force = null): array
    {
        if (! $cart->canBuy($this, $force)) {
            throw new ProductEnded(trans('wallet::errors.product_stock'));
        }

        $self = $this;

        return make(DbService::class)->transaction(static function () use ($self, $cart, $force) {
            $results = [];
            foreach ($cart->getItems() as $product) {
                if ($force) {
                    $results[] = make(CommonService::class)->forceTransfer(
                        $self,
                        $product,
                        $product->getAmountProduct($self),
                        make(MetaService::class)->getMeta($cart, $product),
                        Transfer::STATUS_PAID
                    );

                    continue;
                }

                $results[] = make(CommonService::class)->transfer(
                    $self,
                    $product,
                    $product->getAmountProduct($self),
                    make(MetaService::class)->getMeta($cart, $product),
                    Transfer::STATUS_PAID
                );
            }

            return $results;
        });
    }

    /**
     * @param Cart $cart
     * @return Transfer[]
     * @throws
     */
    public function forcePayCart(Cart $cart): array
    {
        return $this->payCart($cart, true);
    }

    /**
     * @param Cart $cart
     * @param bool $force
     * @param bool $gifts
     * @return bool
     */
    public function safeRefundCart(Cart $cart, bool $force = null, bool $gifts = null): bool
    {
        try {
            return $this->refundCart($cart, $force, $gifts);
        } catch (Throwable $throwable) {
            return false;
        }
    }

    /**
     * @param Cart $cart
     * @param bool $force
     * @param bool $gifts
     * @return bool
     * @throws
     */
    public function refundCart(Cart $cart, bool $force = null, bool $gifts = null): bool
    {
        $self = $this;

        return make(DbService::class)->transaction(static function () use ($self, $cart, $force, $gifts) {
            $results = [];
            $transfers = $cart->alreadyBuy($self, $gifts);
            if (count($transfers) !== count($cart)) {
                throw (new ModelNotFoundException())
                    ->setModel($self->transfers()->getMorphClass());
            }

            foreach ($cart->getItems() as $key => $product) {
                $transfer = $transfers[$key];
                $transfer->load('withdraw.wallet');

                if (! $force) {
                    make(CommonService::class)->verifyWithdraw(
                        $product,
                        $transfer->deposit->amount
                    );
                }

                make(CommonService::class)->forceTransfer(
                    $product,
                    $transfer->withdraw->wallet,
                    $transfer->deposit->amount,
                    make(MetaService::class)->getMeta($cart, $product)
                );

                $results[] = $transfer->update([
                    'status' => Transfer::STATUS_REFUND,
                    'status_last' => $transfer->status,
                ]);
            }

            return count(array_unique($results)) === 1;
        });
    }

    /**
     * @param Cart $cart
     * @param bool $gifts
     * @return bool
     * @throws
     */
    public function forceRefundCart(Cart $cart, bool $gifts = null): bool
    {
        return $this->refundCart($cart, true, $gifts);
    }

    /**
     * @param Cart $cart
     * @param bool $force
     * @return bool
     */
    public function safeRefundGiftCart(Cart $cart, bool $force = null): bool
    {
        try {
            return $this->refundGiftCart($cart, $force);
        } catch (Throwable $throwable) {
            return false;
        }
    }

    /**
     * @param Cart $cart
     * @param bool $force
     * @return bool
     * @throws
     */
    public function refundGiftCart(Cart $cart, bool $force = null): bool
    {
        return $this->refundCart($cart, $force, true);
    }

    /**
     * @param Cart $cart
     * @return bool
     * @throws
     */
    public function forceRefundGiftCart(Cart $cart): bool
    {
        return $this->refundGiftCart($cart, true);
    }

    /**
     * Checks acquired product your wallet.
     *
     * @param Product $product
     * @param bool $gifts
     * @return null|Transfer
     */
    public function paid(Product $product, bool $gifts = null): ?Transfer
    {
        return current(make(Cart::class)->addItem($product)->alreadyBuy($this, $gifts)) ?: null;
    }
}
