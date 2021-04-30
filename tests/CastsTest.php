<?php

namespace Xtwoend\Wallet\Test;

use Xtwoend\Wallet\Test\Factories\BuyerFactory;
use Xtwoend\Wallet\Test\Factories\UserFactory;
use Xtwoend\Wallet\Test\Models\Buyer;
use Xtwoend\Wallet\Test\Models\User;

class CastsTest extends TestCase
{
    /**
     * @return void
     */
    public function testModelWallet(): void
    {
        /**
         * @var Buyer $buyer
         */
        $buyer = BuyerFactory::new()->create();
        self::assertEquals($buyer->balance, 0);

        self::assertIsInt($buyer->wallet->getKey());
        self::assertEquals($buyer->wallet->getCasts()['id'], 'int');

        config(['wallet.wallet.casts.id' => 'string']);
        self::assertIsString($buyer->wallet->getKey());
        self::assertEquals($buyer->wallet->getCasts()['id'], 'string');
    }

    /**
     * @return void
     */
    public function testModelTransfer(): void
    {
        /**
         * @var Buyer $buyer
         * @var User $user
         */
        $buyer = BuyerFactory::new()->create();
        $user = UserFactory::new()->create();
        self::assertEquals($buyer->balance, 0);
        self::assertEquals($user->balance, 0);

        $deposit = $user->deposit(1000);
        self::assertEquals($user->balance, $deposit->amount);

        $transfer = $user->transfer($buyer, 700);

        self::assertIsInt($transfer->getKey());
        self::assertEquals($transfer->getCasts()['id'], 'int');

        config(['wallet.transfer.casts.id' => 'string']);
        self::assertIsString($transfer->getKey());
        self::assertEquals($transfer->getCasts()['id'], 'string');
    }

    /**
     * @return void
     */
    public function testModelTransaction(): void
    {
        /**
         * @var Buyer $buyer
         */
        $buyer = BuyerFactory::new()->create();
        self::assertEquals($buyer->balance, 0);
        $deposit = $buyer->deposit(1);

        self::assertIsInt($deposit->getKey());
        self::assertEquals($deposit->getCasts()['id'], 'int');

        config(['wallet.transaction.casts.id' => 'string']);
        self::assertIsString($deposit->getKey());
        self::assertEquals($deposit->getCasts()['id'], 'string');
    }
}
