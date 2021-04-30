<?php

namespace Xtwoend\Wallet\Services;

use Closure;
use Throwable;
use Hyperf\DbConnection\Db;
use Hyperf\Database\Query\Expression;
use Hyperf\Database\ConnectionInterface;

/**
 * Class DbService.
 *
 * @codeCoverageIgnore
 */
class DbService
{
    /**
     * @return ConnectionInterface
     */
    public function connection(): ConnectionInterface
    {
        return Db::connection(config('wallet.database.connection'));
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param Closure $callback
     * @param int $attempts
     *
     * @return mixed
     *
     * @throws Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        if ($this->connection()->transactionLevel()) {
            return $callback($this);
        }

        return $this->connection()->transaction($callback, $attempts);
    }

    /**
     * Get a new raw query expression.
     *
     * @param mixed $value
     *
     * @return Expression
     */
    public function raw($value): Expression
    {
        return $this->connection()->raw($value);
    }
}
