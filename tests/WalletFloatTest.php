<?php

namespace Xtwoend\Wallet\Test;

use Xtwoend\Wallet\Exceptions\AmountInvalid;
use Xtwoend\Wallet\Exceptions\BalanceIsEmpty;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Models\Transaction;
use Xtwoend\Wallet\Test\Factories\UserFloatFactory;
use Xtwoend\Wallet\Test\Models\UserFloat as User;

class WalletFloatTest extends TestCase
{
    /**
     * @return void
     */
    public function testDeposit(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals(0, $user->balance);
        self::assertEquals(0, $user->balanceFloat);

        $user->depositFloat(.1);
        self::assertEquals(10, $user->balance);
        self::assertEquals(.1, $user->balanceFloat);

        $user->depositFloat(1.25);
        self::assertEquals(135, $user->balance);
        self::assertEquals(1.35, $user->balanceFloat);

        $user->deposit(865);
        self::assertEquals(1000, $user->balance);
        self::assertEquals(10, $user->balanceFloat);

        self::assertEquals(3, $user->transactions()->count());

        $user->withdraw($user->balance);
        self::assertEquals(0, $user->balance);
        self::assertEquals(0, $user->balanceFloat);
    }

    /**
     * @return void
     */
    public function testInvalidDeposit(): void
    {
        $this->expectException(AmountInvalid::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.price_positive'));
        $user = UserFloatFactory::new()->create();
        $user->depositFloat(-1);
    }

    /**
     * @return void
     */
    public function testWithdraw(): void
    {
        $this->expectException(BalanceIsEmpty::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.wallet_empty'));

        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->depositFloat(1);
        self::assertEquals(1, $user->balanceFloat);

        $user->withdrawFloat(.1);
        self::assertEquals(0.9, $user->balanceFloat);

        $user->withdrawFloat(.81);
        self::assertEquals(.09, $user->balanceFloat);

        $user->withdraw(9);
        self::assertEquals(0, $user->balance);

        $user->withdraw(1);
    }

    /**
     * @return void
     */
    public function testInvalidWithdraw(): void
    {
        $this->expectException(BalanceIsEmpty::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.wallet_empty'));
        $user = UserFloatFactory::new()->create();
        $user->withdrawFloat(-1);
    }

    /**
     * @return void
     */
    public function testTransfer(): void
    {
        /**
         * @var User $first
         * @var User $second
         */
        [$first, $second] = UserFloatFactory::times(2)->create();
        self::assertNotEquals($first->id, $second->id);
        self::assertEquals($first->balanceFloat, 0);
        self::assertEquals($second->balanceFloat, 0);

        $first->depositFloat(1);
        self::assertEquals($first->balanceFloat, 1);

        $second->depositFloat(1);
        self::assertEquals($second->balanceFloat, 1);

        $first->transferFloat($second, 1);
        self::assertEquals($first->balanceFloat, 0);
        self::assertEquals($second->balanceFloat, 2);

        $second->transferFloat($first, 1);
        self::assertEquals($second->balanceFloat, 1);
        self::assertEquals($first->balanceFloat, 1);

        $second->transferFloat($first, 1);
        self::assertEquals($second->balanceFloat, 0);
        self::assertEquals($first->balanceFloat, 2);

        $first->withdrawFloat($first->balanceFloat);
        self::assertEquals($first->balanceFloat, 0);

        self::assertNull($first->safeTransferFloat($second, 1));
        self::assertEquals($first->balanceFloat, 0);
        self::assertEquals($second->balanceFloat, 0);

        self::assertNotNull($first->forceTransferFloat($second, 1));
        self::assertEquals($first->balanceFloat, -1);
        self::assertEquals($second->balanceFloat, 1);

        self::assertNotNull($second->forceTransferFloat($first, 1));
        self::assertEquals($first->balanceFloat, 0);
        self::assertEquals($second->balanceFloat, 0);
    }

    /**
     * @return void
     */
    public function testTransferYourself(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals(0, $user->balanceFloat);

        $user->depositFloat(1);
        $user->transferFloat($user, 1);
        self::assertEquals(100, $user->balance);

        $user->withdrawFloat($user->balanceFloat);
        self::assertEquals(0, $user->balance);
    }

    /**
     * @return void
     */
    public function testBalanceIsEmpty(): void
    {
        $this->expectException(BalanceIsEmpty::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.wallet_empty'));

        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals(0, $user->balance);
        $user->withdrawFloat(1);
    }

    /**
     * @return void
     */
    public function testConfirmed(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals($user->balance, 0);

        $user->depositFloat(1);
        self::assertEquals($user->balanceFloat, 1);

        $user->withdrawFloat(1, null, false);
        self::assertEquals($user->balanceFloat, 1);

        self::assertTrue($user->canWithdrawFloat(1));
        $user->withdrawFloat(1);
        self::assertFalse($user->canWithdrawFloat(1));
        $user->forceWithdrawFloat(1);
        self::assertEquals($user->balanceFloat, -1);
        $user->depositFloat(1);
        self::assertEquals($user->balanceFloat, 0);
    }

    /**
     * @return void
     */
    public function testMantissa(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals($user->balance, 0);

        $user->deposit(1000000);
        self::assertEquals($user->balance, 1000000);
        self::assertEquals($user->balanceFloat, 10000.00);

        $transaction = $user->withdrawFloat(2556.72);
        self::assertEquals($transaction->amount, -255672);
        self::assertEquals($transaction->amountFloat, -2556.72);
        self::assertEquals($transaction->type, Transaction::TYPE_WITHDRAW);

        self::assertEquals($user->balance, 1000000 - 255672);
        self::assertEquals($user->balanceFloat, 10000.00 - 2556.72);

        $transaction = $user->depositFloat(2556.72 * 2);
        self::assertEquals($transaction->amount, 255672 * 2);
        self::assertEquals($transaction->amountFloat, 2556.72 * 2);
        self::assertEquals($transaction->type, Transaction::TYPE_DEPOSIT);

        self::assertEquals($user->balance, 1000000 + 255672);
        self::assertEquals($user->balanceFloat, 10000.00 + 2556.72);
    }

    /**
     * @return void
     */
    public function testUpdateTransaction(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals($user->balance, 0);

        $user->deposit(1000000);
        self::assertEquals($user->balance, 1000000);
        self::assertEquals($user->balanceFloat, 10000.00);

        $transaction = $user->withdrawFloat(2556.72);
        self::assertEquals($transaction->amount, -255672);
        self::assertEquals($transaction->amountFloat, -2556.72);
        self::assertEquals($transaction->type, Transaction::TYPE_WITHDRAW);

        $transaction->type = Transaction::TYPE_DEPOSIT;
        $transaction->amountFloat = 2556.72;
        self::assertTrue($transaction->save());
        self::assertTrue($user->wallet->refreshBalance());

        self::assertEquals($transaction->amount, 255672);
        self::assertEquals($transaction->amountFloat, 2556.72);
        self::assertEquals($transaction->type, Transaction::TYPE_DEPOSIT);

        self::assertEquals($user->balance, 1000000 + 255672);
        self::assertEquals($user->balanceFloat, 10000.00 + 2556.72);
    }

    /**
     * @return void
     */
    public function testMathRounding(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals($user->balance, 0);

        $user->deposit(1000000);
        self::assertEquals($user->balance, 1000000);
        self::assertEquals($user->balanceFloat, 10000.00);

        $transaction = $user->withdrawFloat(0.2 + 0.1);
        self::assertEquals($transaction->amount, -30);
        self::assertEquals($transaction->type, Transaction::TYPE_WITHDRAW);

        $transaction = $user->withdrawFloat(0.2 + 0.105);
        self::assertEquals($transaction->amount, -31);
        self::assertEquals($transaction->type, Transaction::TYPE_WITHDRAW);

        $transaction = $user->withdrawFloat(0.2 + 0.104);
        self::assertEquals($transaction->amount, -30);
        self::assertEquals($transaction->type, Transaction::TYPE_WITHDRAW);
    }

    /**
     * @return void
     */
    public function testEther(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->wallet->decimal_places = 18;
        $user->wallet->save();

        $math = app(Mathable::class);

        $user->depositFloat('545.8754855274419');
        self::assertEquals('545875485527441900000', $user->balance);
        self::assertEquals(0, $math->compare($user->balanceFloat, '545.8754855274419'));
    }

    /**
     * @return void
     */
    public function testBitcoin(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->wallet->decimal_places = 32; // bitcoin wallet
        $user->wallet->save();

        $math = app(Mathable::class);

        for ($i = 0; $i < 256; $i++) {
            $user->depositFloat('0.00000001'); // Satoshi
        }

        self::assertEquals($user->balance, '256'.str_repeat('0', 32 - 8));
        self::assertEquals(0, $math->compare($user->balanceFloat, '0.00000256'));

        $user->deposit(256 .str_repeat('0', 32));
        $user->depositFloat('0.'.str_repeat('0', 31).'1');

        [$q, $r] = explode('.', $user->balanceFloat, 2);
        self::assertEquals(strlen($r), $user->wallet->decimal_places);
        self::assertEquals('25600000256000000000000000000000001', $user->balance);
        self::assertEquals('256.00000256000000000000000000000001', $user->balanceFloat);
    }

    /**
     * Case from @ucanbehack.
     * @see https://github.com/Xtwoend/laravel-wallet/issues/149
     */
    public function testBitcoin2(): void
    {
        /**
         * @var User $user
         */
        $user = UserFloatFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->wallet->decimal_places = 8;
        $user->wallet->save();

        $user->depositFloat(0.09699977);

        $user->wallet->refreshBalance();
        $user->refresh();

        self::assertEquals(0.09699977, $user->balanceFloat);
        self::assertEquals(9699977, $user->balance);
    }
}
