<?php

namespace Xtwoend\Wallet\Models;

use function make;
use function config;
use Hyperf\Utils\Str;
use function array_merge;
use function array_key_exists;
use Xtwoend\Wallet\Traits\HasGift;
use Hyperf\DbConnection\Model\Model;
use Xtwoend\Wallet\Traits\CanConfirm;
use Xtwoend\Wallet\Traits\CanExchange;
use Xtwoend\Wallet\Traits\CanPayFloat;
use Xtwoend\Wallet\Interfaces\Customer;
use Xtwoend\Wallet\Interfaces\Confirmable;
use Xtwoend\Wallet\Interfaces\WalletFloat;
use Xtwoend\Wallet\Services\WalletService;
use Xtwoend\Wallet\Interfaces\Exchangeable;
use Hyperf\Database\Model\Relations\MorphTo;

/**
 * Class Wallet.
 * @property string $holder_type
 * @property int $holder_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property array $meta
 * @property int $balance
 * @property int $decimal_places
 * @property \Xtwoend\Wallet\Interfaces\Wallet $holder
 * @property-read string $currency
 */
class Wallet extends Model implements Customer, WalletFloat, Confirmable, Exchangeable
{
    use CanConfirm;
    use CanExchange;
    use CanPayFloat;
    use HasGift;

    /**
     * @var array
     */
    protected $fillable = [
        'holder_type',
        'holder_id',
        'name',
        'slug',
        'description',
        'meta',
        'balance',
        'decimal_places',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'decimal_places' => 'int',
        'meta' => 'json',
    ];

    /**
     * {@inheritdoc}
     */
    public function getCasts(): array
    {
        return array_merge(
            parent::getCasts(),
            config('wallet.wallet.casts', [])
        );
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        if (! $this->table) {
            $this->table = config('wallet.wallet.table', 'wallets');
        }

        return parent::getTable();
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function setNameAttribute(string $name): void
    {
        $this->attributes['name'] = $name;

        /**
         * Must be updated only if the model does not exist
         *  or the slug is empty.
         */
        if (! $this->exists && ! array_key_exists('slug', $this->attributes)) {
            $this->attributes['slug'] = Str::slug($name);
        }
    }

    /**
     * Under ideal conditions, you will never need a method.
     * Needed to deal with out-of-sync.
     *
     * @return bool
     */
    public function refreshBalance(): bool
    {
        return make(WalletService::class)->refresh($this);
    }

    /**
     * The method adjusts the balance by adding an additional transaction.
     * Used wisely, it can lead to serious problems.
     *
     * @return bool
     */
    public function adjustmentBalance(): bool
    {
        try {
            make(WalletService::class)->adjustment($this);

            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @return float|int
     */
    public function getAvailableBalance()
    {
        return $this->transactions()
            ->where('wallet_id', $this->getKey())
            ->where('confirmed', true)
            ->sum('amount');
    }

    /**
     * @return MorphTo
     */
    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return string
     */
    public function getCurrencyAttribute(): string
    {
        $currencies = config('wallet.currencies', []);

        return $currencies[$this->slug] ??
            $this->meta['currency'] ??
            Str::upper($this->slug);
    }

    /**
     * Issue loop get attribute balance
     *
     * @return void
     */
    public function getBalanceAttribute()
    {
        return $this->attributes['balance'];
    }
}
