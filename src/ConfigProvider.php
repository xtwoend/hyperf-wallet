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
use Xtwoend\Wallet\Simple\Store;
use Xtwoend\Wallet\Simple\BrickMath;
use Xtwoend\Wallet\Services\DbService;
use Xtwoend\Wallet\Interfaces\Mathable;
use Xtwoend\Wallet\Interfaces\Rateable;
use Xtwoend\Wallet\Interfaces\Storable;
use Xtwoend\Wallet\Services\LockService;
use Xtwoend\Wallet\Services\MetaService;
use Xtwoend\Wallet\Services\CommonService;
use Xtwoend\Wallet\Services\WalletService;
use Xtwoend\Wallet\Services\ExchangeService;

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
                    'description' => 'The config for hyperf wallet.',
                    'source' => __DIR__ . '/../publish/wallet.php',
                    'destination' => BASE_PATH . '/config/autoload/wallet.php',
                ],
                [
                    'id' => 'migration',
                    'description' => 'Migration database wellet table.',
                    'source' => __DIR__ . '/../database/2021_04_30_073129_create_wallets_table.php',
                    'destination' => BASE_PATH . '/migrations/2021_04_30_073129_create_wallets_table.php',
                ],
                [
                    'id' => 'migration',
                    'description' => 'Migration database transfer table.',
                    'source' => __DIR__ . '/../database/2021_04_30_073147_create_transfers_table.php',
                    'destination' => BASE_PATH . '/migrations/2021_04_30_073147_create_transfers_table.php',
                ],
                [
                    'id' => 'migration',
                    'description' => 'Migration database transaction table.',
                    'source' => __DIR__ . '/../database/2021_04_30_073137_create_transactions_table.php',
                    'destination' => BASE_PATH . '/migrations/2021_04_30_073137_create_transactions_table.php',
                ],
            ],
        ];
    }
}
