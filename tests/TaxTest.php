<?php

namespace Xtwoend\Wallet\Test;

use Xtwoend\Wallet\Exceptions\InsufficientFunds;
use Xtwoend\Wallet\Models\Transaction;
use Xtwoend\Wallet\Test\Factories\BuyerFactory;
use Xtwoend\Wallet\Test\Factories\ItemTaxFactory;
use Xtwoend\Wallet\Test\Models\Buyer;
use Xtwoend\Wallet\Test\Models\ItemTax;

class TaxTest extends TestCase
{
    /**
     * @return void
     */
    public function testPay(): void
    {
        /**
         * @var Buyer $buyer
         * @var ItemTax $product
         */
        $buyer = BuyerFactory::new()->create();
        $product = ItemTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        $fee = (int) ($product->getAmountProduct($buyer) * $product->getFeePercent() / 100);
        $balance = $product->getAmountProduct($buyer) + $fee;

        self::assertEquals($buyer->balance, 0);
        $buyer->deposit($balance);

        self::assertNotEquals($buyer->balance, 0);
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);

        /**
         * @var Transaction $withdraw
         * @var Transaction $deposit
         */
        $withdraw = $transfer->withdraw;
        $deposit = $transfer->deposit;

        self::assertEquals($withdraw->amount, -$balance);
        self::assertEquals($deposit->amount, $product->getAmountProduct($buyer));
        self::assertNotEquals($deposit->amount, $withdraw->amount);
        self::assertEquals($transfer->fee, $fee);

        $buyer->refund($product);
        self::assertEquals($buyer->balance, $deposit->amount);
        self::assertEquals($product->balance, 0);

        $buyer->withdraw($buyer->balance);
        self::assertEquals($buyer->balance, 0);
    }

    /**
     * @return void
     */
    public function testGift(): void
    {
        /**
         * @var Buyer $santa
         * @var Buyer $child
         * @var ItemTax $product
         */
        [$santa, $child] = BuyerFactory::times(2)->create();
        $product = ItemTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        $fee = (int) ($product->getAmountProduct($santa) * $product->getFeePercent() / 100);
        $balance = $product->getAmountProduct($santa) + $fee;

        self::assertEquals($santa->balance, 0);
        self::assertEquals($child->balance, 0);
        $santa->deposit($balance);

        self::assertNotEquals($santa->balance, 0);
        self::assertEquals($child->balance, 0);
        $transfer = $santa->wallet->gift($child, $product);
        self::assertNotNull($transfer);

        /**
         * @var Transaction $withdraw
         * @var Transaction $deposit
         */
        $withdraw = $transfer->withdraw;
        $deposit = $transfer->deposit;

        self::assertEquals($withdraw->amount, -$balance);
        self::assertEquals($deposit->amount, $product->getAmountProduct($santa));
        self::assertNotEquals($deposit->amount, $withdraw->amount);
        self::assertEquals($transfer->fee, $fee);

        self::assertFalse($santa->safeRefundGift($product));
        self::assertTrue($child->refundGift($product));
        self::assertEquals($santa->balance, $deposit->amount);
        self::assertEquals($child->balance, 0);
        self::assertEquals($product->balance, 0);

        $santa->withdraw($santa->balance);
        self::assertEquals($santa->balance, 0);
    }

    /**
     * @return void
     */
    public function testGiftFail(): void
    {
        $this->expectException(InsufficientFunds::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.insufficient_funds'));

        /**
         * @var Buyer $santa
         * @var Buyer $child
         * @var ItemTax $product
         */
        [$santa, $child] = BuyerFactory::times(2)->create();
        $product = ItemTaxFactory::new()->create([
            'price' => 200,
            'quantity' => 1,
        ]);

        self::assertEquals($santa->balance, 0);
        self::assertEquals($child->balance, 0);
        $santa->deposit($product->getAmountProduct($santa));

        self::assertNotEquals($santa->balance, 0);
        self::assertEquals($child->balance, 0);
        $santa->wallet->gift($child, $product);

        self::assertEquals($santa->balance, 0);
    }
}
