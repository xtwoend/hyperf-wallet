<?php

namespace Xtwoend\Wallet\Services;

use Hyperf\Utils\Str;
use Hyperf\Cache\Cache;
use Hyperf\DbConnection\Model\Model;
use Xtwoend\Wallet\Objects\EmptyLock;

class LockService
{
    /**
     * @var string
     */
    protected $uniqId;

    /**
     * LockService constructor.
     */
    public function __construct()
    {
        $this->uniqId = Str::random();
    }

    /**
     * @param object $self
     * @param string $name
     * @param \Closure $closure
     *
     * @return mixed
     */
    public function lock($self, string $name, \Closure $closure)
    {
        return $this->lockProvider($self, $name, (int) config('wallet.lock.seconds', 1))
            ->get($this->bindTo($self, $closure));
    }

    /**
     * @param object $self
     * @param \Closure $closure
     * @return \Closure
     *
     * @throws
     */
    protected function bindTo($self, \Closure $closure): \Closure
    {
        $reflect = new \ReflectionFunction($closure);
        if (strpos((string) $reflect, 'static') === false) {
            return $closure->bindTo($self);
        }

        return $closure;
    }

    /**
     * @return Store|null
     *
     * @codeCoverageIgnore
     */
    protected function cache(): ?Store
    {
        try {
            return Cache::store(config('wallet.lock.cache'))
                ->getStore();
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    /**
     * @param object $self
     * @param string $name
     * @param int $seconds
     *
     * @return 
     */
    protected function lockProvider($self, string $name, int $seconds)
    {
        $store = $this->cache();
        $enabled = $store && config('wallet.lock.enabled', false);

        // fixme: CodeClimate
        // @codeCoverageIgnoreStart
        if ($enabled) {
            $class = \get_class($self);
            $uniqId = $class.$this->uniqId;
            if ($self instanceof Model) {
                $uniqId = $class.$self->getKey();
            }

            return $store->lock("$name.$uniqId", $seconds);
        }
        // @codeCoverageIgnoreEnd

        return make(EmptyLock::class);
    }
}
