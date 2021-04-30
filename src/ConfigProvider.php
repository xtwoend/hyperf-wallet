<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Xtwoend\Wallet;

use Xtwoend\Wallet\Simple\Rate;
use Xtwoend\Wallet\Interfaces\Rateable;


class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                Rateable::class => Rate::class,
                Storable::class => Store::class,
                Mathable::class => BrickMath::class,
                DbService::class => DbService::class,
                ExchangeService::class => ExchangeService::class,
                CommonService::class => CommonService::class,
                WalletService::class => WalletService::class,
                LockService::class => LockService::class,
                MetaService::class => MetaService::class
            ],
            'processes' => [
                // 
            ],
            'listeners' => [
                // 
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for config center.',
                    'source' => __DIR__ . '/../publish/wallet.php',
                    'destination' => BASE_PATH . '/config/autoload/wallet.php',
                ],
            ],
        ];
    }
}
