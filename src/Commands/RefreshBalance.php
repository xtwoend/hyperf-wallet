<?php

namespace Xtwoend\Wallet\Commands;

use Xtwoend\Wallet\Models\Wallet;
use Xtwoend\Wallet\Services\WalletService;
use Hyperf\Command\Command;

/**
 * Class RefreshBalance.
 */
class RefreshBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculates all wallets';

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('wallet:refresh');
        $this->container = $container;
    }

    /**
     * @return void
     *
     * @throws
     */
    public function handle(): void
    {
        Wallet::query()->each([make(WalletService::class), 'refresh']);
    }
}
