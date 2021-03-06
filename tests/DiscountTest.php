<?php

namespace Xtwoend\Wallet\Test;

use Xtwoend\Wallet\Exceptions\ProductEnded;
use Xtwoend\Wallet\Models\Transaction;
use Xtwoend\Wallet\Models\Transfer;
use Xtwoend\Wallet\Models\Wallet;
use Xtwoend\Wallet\Test\Factories\BuyerFactory;
use Xtwoend\Wallet\Test\Factories\ItemDiscountFactory;
use Xtwoend\Wallet\Test\Models\Buyer;
use Xtwoend\Wallet\Test\Models\Item;
use Xtwoend\Wallet\Test\Models\ItemDiscount;

class DiscountTest extends TestCase
{
    /**
     * @return void
     */
    public function testPay(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create();

        self::assertEquals(0, $buyer->balance);
        $buyer->deposit($product->getAmountProduct($buyer));

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertEquals(Transfer::STATUS_PAID, $transfer->status);

        self::assertEquals(
            $buyer->balance,
            $product->getPersonalDiscount($buyer)
        );

        self::assertEquals(
            $transfer->discount,
            $product->getPersonalDiscount($buyer)
        );

        /**
         * @var Transaction $withdraw
         * @var Transaction $deposit
         */
        $withdraw = $transfer->withdraw;
        $deposit = $transfer->deposit;

        self::assertInstanceOf(Transaction::class, $withdraw);
        self::assertInstanceOf(Transaction::class, $deposit);

        self::assertInstanceOf(Buyer::class, $withdraw->payable);
        self::assertInstanceOf(Item::class, $deposit->payable);

        self::assertEquals($buyer->getKey(), $withdraw->payable->getKey());
        self::assertEquals($product->getKey(), $deposit->payable->getKey());

        self::assertInstanceOf(Buyer::class, $transfer->from->holder);
        self::assertInstanceOf(Wallet::class, $transfer->from);
        self::assertInstanceOf(Item::class, $transfer->to);
        self::assertInstanceOf(Wallet::class, $transfer->to->wallet);

        self::assertEquals($buyer->wallet->getKey(), $transfer->from->getKey());
        self::assertEquals($buyer->getKey(), $transfer->from->holder->getKey());
        self::assertEquals($product->getKey(), $transfer->to->getKey());
    }

    /**
     * @return void
     */
    public function testItemTransactions(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create();

        self::assertEquals(0, $buyer->balance);
        $buyer->deposit($product->getAmountProduct($buyer));

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertEquals(Transfer::STATUS_PAID, $transfer->status);

        self::assertEquals(
            $buyer->balance,
            $product->getPersonalDiscount($buyer)
        );

        self::assertEquals(
            $transfer->discount,
            $product->getPersonalDiscount($buyer)
        );

        /**
         * @var Transaction $withdraw
         * @var Transaction $deposit
         */
        $withdraw = $transfer->withdraw;
        $deposit = $transfer->deposit;

        self::assertInstanceOf(Transaction::class, $withdraw);
        self::assertInstanceOf(Transaction::class, $deposit);

        self::assertTrue($withdraw->is(
            $buyer->transactions()
                ->where('type', Transaction::TYPE_WITHDRAW)
                ->latest()
                ->first()
        ));

        self::assertTrue($deposit->is($product->transactions()->latest()->first()));
    }

    /**
     * @return void
     */
    public function testRefund(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertEquals(0, $buyer->balance);
        $buyer->deposit($product->getAmountProduct($buyer));

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertEquals(Transfer::STATUS_PAID, $transfer->status);

        self::assertEquals(
            $transfer->discount,
            $product->getPersonalDiscount($buyer)
        );

        self::assertTrue($buyer->refund($product));
        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        self::assertEquals(0, $product->balance);

        $transfer->refresh();
        self::assertEquals(Transfer::STATUS_REFUND, $transfer->status);

        self::assertFalse($buyer->safeRefund($product));
        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));

        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertEquals($buyer->balance, $product->getPersonalDiscount($buyer));
        self::assertEquals(
            $product->balance,
            $product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)
        );

        self::assertEquals(Transfer::STATUS_PAID, $transfer->status);

        self::assertTrue($buyer->refund($product));
        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        self::assertEquals(0, $product->balance);

        $transfer->refresh();
        self::assertEquals(Transfer::STATUS_REFUND, $transfer->status);
    }

    /**
     * @return void
     */
    public function testForceRefund(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertEquals(0, $buyer->balance);
        $buyer->deposit($product->getAmountProduct($buyer));

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));

        $transfer = $buyer->pay($product);
        self::assertEquals($buyer->balance, $product->getPersonalDiscount($buyer));

        self::assertEquals(
            $product->balance,
            $product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)
        );

        self::assertEquals(
            $transfer->discount,
            $product->getPersonalDiscount($buyer)
        );

        $product->withdraw($product->balance);
        self::assertEquals(0, $product->balance);

        self::assertFalse($buyer->safeRefund($product));
        self::assertTrue($buyer->forceRefund($product));

        self::assertEquals(
            $product->balance, -($product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer))
        );

        self::assertEquals($buyer->balance, $product->getAmountProduct($buyer));
        $product->deposit(-$product->balance);
        $buyer->withdraw($buyer->balance);

        self::assertEquals(0, $product->balance);
        self::assertEquals(0, $buyer->balance);
    }

    /**
     * @return void
     */
    public function testOutOfStock(): void
    {
        $this->expectException(ProductEnded::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.product_stock'));

        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create([
            'quantity' => 1,
        ]);

        $buyer->deposit($product->getAmountProduct($buyer));
        $buyer->pay($product);
        $buyer->pay($product);
    }

    /**
     * @return void
     */
    public function testForcePay(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertEquals(0, $buyer->balance);
        $buyer->forcePay($product);

        self::assertEquals(
            $buyer->balance, -($product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer))
        );

        $buyer->deposit(-$buyer->balance);
        self::assertEquals(0, $buyer->balance);
    }

    /**
     * @return void
     */
    public function testPayFree(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertEquals(0, $buyer->balance);

        $transfer = $buyer->payFree($product);
        self::assertEquals(Transaction::TYPE_DEPOSIT, $transfer->deposit->type);
        self::assertEquals(Transaction::TYPE_WITHDRAW, $transfer->withdraw->type);

        self::assertEquals(0, $buyer->balance);
        self::assertEquals(0, $product->balance);

        $buyer->refund($product);
        self::assertEquals(0, $buyer->balance);
        self::assertEquals(0, $product->balance);
    }

    public function testFreePay(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create([
            'quantity' => 1,
        ]);

        $buyer->forceWithdraw(1000);
        self::assertEquals(-1000, $buyer->balance);

        $transfer = $buyer->payFree($product);
        self::assertEquals(Transaction::TYPE_DEPOSIT, $transfer->deposit->type);
        self::assertEquals(Transaction::TYPE_WITHDRAW, $transfer->withdraw->type);

        self::assertEquals(-1000, $buyer->balance);
        self::assertEquals(0, $product->balance);

        $buyer->refund($product);
        self::assertEquals(-1000, $buyer->balance);
        self::assertEquals(0, $product->balance);
    }

    /**
     * @return void
     */
    public function testPayFreeOutOfStock(): void
    {
        $this->expectException(ProductEnded::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.product_stock'));

        /**
         * @var Buyer $buyer
         * @var ItemDiscount $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemDiscountFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertNotNull($buyer->payFree($product));
        $buyer->payFree($product);
    }
}
