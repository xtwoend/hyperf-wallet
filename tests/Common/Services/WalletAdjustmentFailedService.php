<?php

namespace Xtwoend\Wallet\Test\Common\Services;

use Xtwoend\Wallet\Models\Wallet as WalletModel;
use Xtwoend\Wallet\Services\WalletService;
use Doctrine\DBAL\Exception\InvalidArgumentException;

class WalletAdjustmentFailedService extends WalletService
{
    /**
     * @param WalletModel $wallet
     * @param array|null $meta
     * @throws InvalidArgumentException
     */
    public function adjustment(WalletModel $wallet, ?array $meta = null): void
    {
        throw new InvalidArgumentException(__METHOD__);
    }
}
