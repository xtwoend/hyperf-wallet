<?php

namespace Xtwoend\Wallet\Test;

use Xtwoend\Wallet\Exceptions\AmountInvalid;
use Xtwoend\Wallet\Exceptions\BalanceIsEmpty;
use Xtwoend\Wallet\Exceptions\InsufficientFunds;
use Xtwoend\Wallet\Models\Transaction;
use Xtwoend\Wallet\Test\Factories\UserFactory;
use Xtwoend\Wallet\Test\Models\User;
use Illuminate\Database\Eloquent\Collection;

class WalletTest extends TestCase
{
    /**
     * @return void
     */
    public function testDeposit(): void
    {
        /**
         * @var User $user
         */
        $user = UserFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->deposit(10);
        self::assertEquals(10, $user->balance);

        $user->deposit(10);
        self::assertEquals(20, $user->balance);

        $user->deposit(980);
        self::assertEquals(1000, $user->balance);

        self::assertEquals(3, $user->transactions()->count());

        $user->withdraw($user->balance);
        self::assertEquals(0, $user->balance);

        self::assertEquals(
            3,
            $user->transactions()
                ->where(['type' => Transaction::TYPE_DEPOSIT])
                ->count()
        );

        self::assertEquals(
            1,
            $user->transactions()
                ->where(['type' => Transaction::TYPE_WITHDRAW])
                ->count()
        );

        self::assertEquals(4, $user->transactions()->count());
    }

    /**
     * @return void
     */
    public function testInvalidDeposit(): void
    {
        $this->expectException(AmountInvalid::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.price_positive'));

        /**
         * @var User $user
         */
        $user = UserFactory::new()->create();
        $user->deposit(-1);
    }

    /**
     * @return void
     */
    public function testFindUserByExistsWallet(): void
    {
        /**
         * @var User[]|Collection $users
         */
        $users = UserFactory::times(10)->create();
        self::assertCount(10, $users);

        /**
         * @var User $user
         */
        $user = $users->first();
        self::assertEquals(0, $user->balance); // create default wallet
        self::assertTrue($user->wallet->exists);

        $ids = [];
        foreach ($users as $other) {
            $ids[] = $other->id;
            if ($user !== $other) {
                self::assertFalse($other->wallet->exists);
            }
        }

        self::assertCount(
            1,
            User::query()
                ->has('wallet')
                ->whereIn('id', $ids)
                ->get()
        );

        self::assertCount(
            9,
            User::query()
                ->has('wallet', '<')
                ->whereIn('id', $ids)
                ->get()
        );
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
        $user = UserFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->deposit(100);
        self::assertEquals(100, $user->balance);

        $user->withdraw(10);
        self::assertEquals(90, $user->balance);

        $user->withdraw(81);
        self::assertEquals(9, $user->balance);

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

        /**
         * @var User $user
         */
        $user = UserFactory::new()->create();
        $user->withdraw(-1);
    }

    /**
     * @return void
     */
    public function testInsufficientFundsWithdraw(): void
    {
        $this->expectException(InsufficientFunds::class);
        $this->expectExceptionMessageStrict(trans('wallet::errors.insufficient_funds'));

        /**
         * @var User $user
         */
        $user = UserFactory::new()->create();
        $user->deposit(1);
        $user->withdraw(2);
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
        [$first, $second] = UserFactory::times(2)->create();
        self::assertNotEquals($first->id, $second->id);
        self::assertEquals(0, $first->balance);
        self::assertEquals(0, $second->balance);

        $first->deposit(100);
        self::assertEquals(100, $first->balance);

        $second->deposit(100);
        self::assertEquals(100, $second->balance);

        $first->transfer($second, 100);
        self::assertEquals(0, $first->balance);
        self::assertEquals(200, $second->balance);

        $second->transfer($first, 100);
        self::assertEquals(100, $second->balance);
        self::assertEquals(100, $first->balance);

        $second->transfer($first, 100);
        self::assertEquals(0, $second->balance);
        self::assertEquals(200, $first->balance);

        $first->withdraw($first->balance);
        self::assertEquals(0, $first->balance);

        self::assertNull($first->safeTransfer($second, 100));
        self::assertEquals(0, $first->balance);
        self::assertEquals(0, $second->balance);

        self::assertNotNull($first->forceTransfer($second, 100));
        self::assertEquals(-100, $first->balance);
        self::assertEquals(100, $second->balance);

        self::assertNotNull($second->forceTransfer($first, 100));
        self::assertEquals(0, $first->balance);
        self::assertEquals(0, $second->balance);
    }

    /**
     * @see https://github.com/Xtwoend/laravel-wallet/issues/286#issue-750353538
     * @return void
     */
    public function testTransferWalletNotExists(): void
    {
        /**
         * @var User $first
         * @var User $second
         */
        [$first, $second] = UserFactory::times(2)->create();
        self::assertNotEquals($first->getKey(), $second->getKey());

        self::assertNotNull($first->deposit(1000));
        self::assertEquals(1000, $first->balance);

        self::assertNotNull($first->transfer($second, 500));
        self::assertEquals(500, $first->balance);
        self::assertEquals(500, $second->balance);
    }

    /**
     * @return void
     */
    public function testTransferYourself(): void
    {
        /**
         * @var User $user
         */
        $user = UserFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->deposit(100);
        $user->transfer($user, 100);
        self::assertEquals(100, $user->balance);

        $user->withdraw($user->balance);
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
        $user = UserFactory::new()->create();
        self::assertEquals(0, $user->balance);
        $user->withdraw(1);
    }

    /**
     * @return void
     */
    public function testConfirmed(): void
    {
        /**
         * @var User $user
         */
        $user = UserFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->deposit(1);
        self::assertEquals(1, $user->balance);

        $user->withdraw(1, null, false);
        self::assertEquals(1, $user->balance);

        $user->withdraw(1);
        self::assertEquals(0, $user->balance);
    }

    /**
     * @return void
     */
    public function testRecalculate(): void
    {
        /**
         * @var User $user
         */
        $user = UserFactory::new()->create();
        self::assertEquals(0, $user->balance);

        $user->deposit(100, null, false);
        self::assertEquals(0, $user->balance);

        $user->transactions()->update(['confirmed' => true]);
        self::assertEquals(0, $user->balance);

        $user->wallet->refreshBalance();
        self::assertEquals(100, $user->balance);

        $user->withdraw($user->balance);
        self::assertEquals(0, $user->balance);
    }
}
