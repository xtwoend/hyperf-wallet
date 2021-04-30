<?php

declare(strict_types=1);

namespace Xtwoend\Wallet\Test\Common;

use Xtwoend\Wallet\WalletServiceProvider as ServiceProvider;

class WalletServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom([
            dirname(__DIR__).'/migrations',
        ]);
    }
}
