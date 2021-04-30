<?php

namespace Xtwoend\Wallet\Test;

use Xtwoend\Wallet\Objects\Operation;
use Xtwoend\Wallet\Test\Common\Models\Transaction;
use Xtwoend\Wallet\Test\Common\Models\TransactionMoney;
use Xtwoend\Wallet\Test\Factories\BuyerFactory;
use Xtwoend\Wallet\Test\Models\Buyer;

class WalletExtensionTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->app->bind(Operation::class, Objects\Operation::class);
    }

    /**
     * @return void
     */
    public function testCustomAttribute(): void
    {
        /**
         * @var Buyer $buyer
         */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        $transaction = $buyer->deposit(1000, ['bank_method' => 'VietComBank']);

        self::assertEquals($transaction->amount, $buyer->balance);
        self::assertInstanceOf(Transaction::class, $transaction);
        self::assertEquals('VietComBank', $transaction->bank_method);
    }

    /**
     * @return void
     */
    public function testTransactionMoneyAttribute(): void
    {
        $this->app['config']->set('wallet.transaction.model', TransactionMoney::class);

        /**
         * @var Buyer $buyer
         * @var TransactionMoney $transaction
         */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        $transaction = $buyer->deposit(1000, ['currency' => 'EUR']);

        self::assertEquals($transaction->amount, $buyer->balance);
        self::assertInstanceOf(TransactionMoney::class, $transaction);
        self::assertEquals(1000, $transaction->currency->getAmount());
        self::assertEquals('EUR', $transaction->currency->getCurrency()->getCode());
    }

    /**
     * @return void
     */
    public function testNoCustomAttribute(): void
    {
        /**
         * @var Buyer $buyer
         */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        $transaction = $buyer->deposit(1000);

        self::assertEquals($transaction->amount, $buyer->balance);
        self::assertInstanceOf(Transaction::class, $transaction);
        self::assertNull($transaction->bank_method);
    }
}
