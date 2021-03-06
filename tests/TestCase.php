<?php

namespace Xtwoend\Wallet\Test;

use Xtwoend\Wallet\Interfaces\Storable;
use Xtwoend\Wallet\Simple\BrickMath;
use Xtwoend\Wallet\Simple\Store;
use Xtwoend\Wallet\Test\Common\Models\Transaction;
use Xtwoend\Wallet\Test\Common\Models\Transfer;
use Xtwoend\Wallet\Test\Common\Models\Wallet;
use Xtwoend\Wallet\Test\Common\Rate;
use Xtwoend\Wallet\Test\Common\WalletServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        app(Storable::class)->fresh();
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        $this->updateConfig($app);

        return [WalletServiceProvider::class];
    }

    protected function updateConfig(Application $app): void
    {
        /** @var $config Repository */
        $config = $app['config'];

        // Bind eloquent models to IoC container
        $app['config']->set('wallet.package.rateable', Rate::class);
        $app['config']->set('wallet.package.storable', Store::class);
        $app['config']->set('wallet.package.mathable', BrickMath::class);

        // database
        $config->set('database.connections.testing.prefix', 'tests');
        $config->set('database.connections.pgsql.prefix', 'tests');
        $config->set('database.connections.mysql.prefix', 'tests');

        $mysql = $config->get('database.connections.mysql');
        $mariadb = array_merge($mysql, ['port' => 3307]);
        $percona = array_merge($mysql, ['port' => 3308]);

        $config->set('database.connections.mariadb', $mariadb);
        $config->set('database.connections.percona', $percona);

        // new table name's
        $config->set('wallet.transaction.table', 'transaction');
        $config->set('wallet.transfer.table', 'transfer');
        $config->set('wallet.wallet.table', 'wallet');

        // override model's
        $config->set('wallet.transaction.model', Transaction::class);
        $config->set('wallet.transfer.model', Transfer::class);
        $config->set('wallet.wallet.model', Wallet::class);

        // wallet
        $config->set('wallet.currencies', [
            'my-usd' => 'USD',
            'my-eur' => 'EUR',
            'my-rub' => 'RUB',
            'def-curr' => 'EUR',
        ]);

        $config->set('wallet.lock.enabled', false);
    }

    /**
     * @param string $message
     */
    public function expectExceptionMessageStrict(string $message): void
    {
        $this->expectExceptionMessageMatches("~^{$message}$~");
    }
}
